<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\PeriodoAcademico;
use App\Models\Bitacora;

class CoordinadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            $esCoord = $user->roles()
                ->where('nombre_rol', 'Coordinador')
                ->orWhere('nombre_rol', 'coordinador')
                ->exists();

            abort_if(!$esCoord, 403, 'No autorizado');

            return $next($request);
        });
    }

    public function index()
{
    $user = Auth::user();

    //defaults
    $total       = PeriodoAcademico::count();
    $borradores  = 0;
    $activos     = 0;
    $publicados  = 0;
    $archivados  = 0;
    if (Schema::hasColumn('periodo_academico', 'estado')) {
        $borradores = PeriodoAcademico::where('estado','Borrador')->count();
        $activos    = PeriodoAcademico::where('estado','Activo')->count();
        $publicados = PeriodoAcademico::where('estado','Publicado')->count();
        $archivados = PeriodoAcademico::where('estado','Archivado')->count();
    }

    $ordenCol = collect(['fecha_inicio','created_at','id_periodo','id'])
        ->first(fn($c) => Schema::hasColumn('periodo_academico', $c)) ?? 'nombre';

    $ultimo    = PeriodoAcademico::orderByDesc($ordenCol)->first();
    $recientes = PeriodoAcademico::orderByDesc($ordenCol)->take(8)->get();

    return view('coordinador.dashboard', compact(
        'user',
        'ultimo',
        'recientes',
        'total',
        'borradores',
        'activos',
        'publicados',
        'archivados',
    ));
}
}
