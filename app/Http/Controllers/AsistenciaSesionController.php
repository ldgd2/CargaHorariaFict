<?php
namespace App\Http\Controllers;

use App\Models\AsistenciaSesion;
use App\Models\CargaHoraria;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Carrera;
use App\Models\PeriodoAcademico;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

class AsistenciaSesionController extends Controller
{
    public function selectorQr()
    {
        $fechaHoy = Carbon::now()->toDateString();
        $periodoActivo = PeriodoAcademico::whereDate('fecha_inicio', '<=', $fechaHoy)
            ->whereDate('fecha_fin', '>=', $fechaHoy)
            ->first();

        if (!$periodoActivo) {
            return response()->json(['message' => 'No hay periodo académico activo.'], 404);
        }
        $carreras = Carrera::where('habilitado', true)->get();

        return view('asistencia.selector_qr', compact('carreras', 'periodoActivo'));
    }
  public function obtenerMateriasPorCarrera($idCarrera)
    {
        $materias = Materia::join('materia_carrera', 'materia_carrera.id_materia', '=', 'materia.id_materia')
            ->where('materia_carrera.id_carrera', $idCarrera)
            ->select('materia.id_materia', 'materia.nombre')
            ->orderBy('materia.nombre')
            ->get();

        return response()->json($materias);
    }

public function obtenerDocentesPorMateria($idMateria)
{
    $cargas = CargaHoraria::with(['docente.usuario', 'grupo', 'aula'])
        ->whereHas('grupo', function ($q) use ($idMateria) {
            $q->where('id_materia', $idMateria);
        })
        ->get();

    $docentes = $cargas->map(function ($ch) {
        $docente    = $ch->docente;
        $usuario    = $docente?->usuario; 
        if ($usuario) {
            $docenteNombre = trim(
                ($usuario->nombre ?? '') . ' ' .
                ($usuario->apellido ?? '')
            );
        } else {
            $docenteNombre = 'Docente #' . ($docente->id_docente ?? '');
        }

        return [
            'carga_horaria_id' => $ch->id_carga,
            'docente_nombre'   => $docenteNombre,
            'grupo_nombre'     => $ch->grupo->nombre_grupo ?? '',
            'aula_nombre'      => $ch->aula->nombre_aula ?? '',
            'dia_semana'       => (int)$ch->dia_semana,
            'hora_inicio'      => $ch->hora_inicio,
            'hora_fin'         => $ch->hora_fin,
        ];
    })->values();

    return response()->json($docentes);
}

    // Generar QR para asistencia de un docente
  public function generarQr($cargaId)
{
    // Obtener la carga horaria y sus relaciones
    $carga = CargaHoraria::with(['docente', 'grupo.materia', 'aula'])->findOrFail($cargaId);

    $hoy = Carbon::today();
    $fechaHoy = $hoy->toDateString();

    // ✅ 1) Solo permitir generar QR el día exacto de la clase
    if ((int)$carga->dia_semana !== $hoy->dayOfWeekIso) {
        return back()->with('error', 'Solo puedes generar el QR el día que te toca clase.');
    }
    $yaMarcado = AsistenciaSesion::where('id_carga', $cargaId)
        ->whereDate('fecha_sesion', $fechaHoy)
        ->exists();

    // ✅ 2) Generar link firmado y TEMPORAL (expira hoy a las 23:59)
    $signedUrl = URL::temporarySignedRoute(
        'docente.asistencia.marcar',
        Carbon::today()->endOfDay(),  
        [
            'carga' => $carga->id_carga,
            'fecha' => $fechaHoy,
        ]
    );

    $fechaSesion = $fechaHoy;
    $qrSvg = $this->makeQrSvg($signedUrl);

    return view('asistencia.qr', [
        'carga'        => $carga,
        'qrSvg'        => $qrSvg,
        'signedUrl'    => $signedUrl,
        'fechaSesion'  => $fechaSesion,
        'yaMarcado'    => $yaMarcado, 
    ]);
}

    // Helper para generar un QR en SVG
    private function makeQrSvg($data)
    {
        $renderer = new ImageRenderer(
            new RendererStyle(260),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }


    // Marcar asistencia al escanear el QR
   public function marcarDesdeQr(Request $request, $cargaId, $fecha)
    {
        // ✅ 1) Proteger por fecha: el QR solo sirve el día para el que fue generado
        $hoy = Carbon::today()->toDateString();
        if ($fecha !== $hoy) {
            return redirect()->route('docente.dashboard')
                ->with('error', 'Este código QR ya expiró o corresponde a otro día.');
        }

        // Obtener la carga horaria con sus relaciones
        $carga = CargaHoraria::with(['docente', 'grupo.materia', 'aula'])->findOrFail($cargaId);

        // ✅ 2) Verificar que la fecha del QR coincida con el día programado de la clase
        $fechaSesion = Carbon::parse($fecha);
        if ((int)$carga->dia_semana !== $fechaSesion->dayOfWeekIso) {
            return redirect()->route('docente.dashboard')
                ->with('error', 'Este código QR no corresponde al día programado de la clase.');
        }

        // ✅ 3) Verificar si YA hay asistencia para esta carga en ese día (uso único)
        $asistenciaExistente = AsistenciaSesion::where('id_carga', $cargaId)
            ->whereDate('fecha_sesion', $fecha)
            ->exists();

        if ($asistenciaExistente) {
            return redirect()->route('docente.dashboard')
                ->with('message', 'Ya se marcó asistencia para esta carga en este día.');
        }
        $horaProgramada = $carga->hora_inicio;
        $horaMarcada = Carbon::now()->format('H:i');
        $programada = Carbon::parse($fecha . ' ' . $horaProgramada);
        $ahora = Carbon::now();
        $desfaseMinutos = $programada->diffInMinutes($ahora, false); 
        if ($desfaseMinutos <= 5) {
            $mood = 'ok';   // Asistencia en tiempo
            $estado = 'Presente';
            $clasificacion = 'puntual';
        } elseif ($desfaseMinutos <= 15) {
            $mood = 'warn'; // Tardanza
            $estado = 'Tardanza';
            $clasificacion = 'tarde';
        } else {
            $mood = 'bad';  // Falta
            $estado = 'Falta';
            $clasificacion = 'muy_tarde';
        }

        // Crear registro de asistencia 
        AsistenciaSesion::create([
            'id_carga'      => $cargaId,
            'fecha_sesion'  => $fecha,
            'hora_registro' => $horaMarcada,
            'tipo_registro' => 'QR',
            'registrado_por'=> Auth::id(),
            'estado'        => $estado,
            'motivo'        => null,
        ]);

        // Pasa todos los datos necesarios a la vista
        return view('asistencia.marcada', [
            'mood'          => $mood,
            'estado'        => $estado,
            'detalleTiempo' => 'Asistencia marcada',
            'horaProgramada'=> $horaProgramada,
            'horaMarcado'   => $horaMarcada,
            'fechaSesion'   => $fecha,
            'docenteNombre' => $carga->docente->nombre ?? '',
            'materia'       => $carga->grupo->materia->nombre ?? '',
            'grupo'         => $carga->grupo->nombre_grupo ?? '',
            'aula'          => $carga->aula->nombre_aula ?? '',
        ]);
    }
}

