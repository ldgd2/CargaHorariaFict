<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Docente;
use App\Models\Usuario;

class Carrera extends Model
{
    protected $table = 'carrera';
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = ['nombre','sigla','jefe_docente_id','habilitado'];
    protected $casts = ['habilitado' => 'boolean'];

    protected static function booted()
    {
        static::creating(function (self $model) {
            // Asegurarse habilitado por defecto cuando se crea desde el importador
            if (Schema::hasColumn('carrera', 'habilitado')) {
                $model->habilitado = $model->habilitado ?? true;
            }

            // Generar sigla automáticamente si la columna existe y no fue provista
            if (Schema::hasColumn('carrera', 'sigla') && empty($model->sigla) && !empty($model->nombre)) {
                $base = static::generarSiglaDesdeNombre($model->nombre);
                $sigla = $base;
                $i = 1;
                while (static::whereRaw('LOWER(sigla)=?', [mb_strtolower($sigla)])->exists()) {
                    $sigla = $base . $i;
                    $i++;
                }
                $model->sigla = $sigla;
            }
        });
    }

    /** Genera una sigla a partir del nombre (acrónimo en mayúsculas) */
    public static function generarSiglaDesdeNombre(string $nombre): string
    {
        // Normalizar acentos y caracteres
        $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N'];
        $clean = strtr($nombre, $map);
        // Tomar iniciales de las palabras
        $parts = preg_split('/\s+/', trim($clean));
        $acronym = '';
        foreach ($parts as $p) {
            if ($p === '') continue;
            $acronym .= mb_substr($p, 0, 1);
            if (mb_strlen($acronym) >= 6) break;
        }
        $acronym = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $acronym));
        if ($acronym === '') {
            // fallback: slug del nombre truncated
            $slug = Str::slug($clean);
            return strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $slug), 0, 6));
        }
        return $acronym;
    }

    public function jefeDocente()
    {
        return $this->belongsTo(Docente::class, 'jefe_docente_id', 'id_docente')
                    ->select('id_docente','nombre');
    }

    
    public function jefe()
    {
        return $this->belongsTo(Docente::class, 'jefe_docente_id', 'id_docente');
    }

    /**
     * Resuelve el id_docente a partir de un identificador que preferentemente
     * será el nro_documento del docente. Si se le pasa un email (valido)
     * intentará resolver a través de usuario->docente.
     * Devuelve el id_docente o null si no lo encuentra.
     */
    public static function resolverJefeId(?string $ident): ?int
    {
        if (!$ident) return null;
        $ident = trim((string)$ident);

        // Si parece un email, buscar por usuario.email -> docente
        if (filter_var($ident, FILTER_VALIDATE_EMAIL)) {
            $user = Usuario::where('email', $ident)->first();
            if (!$user) return null;
            if (Schema::hasColumn('docente','id_usuario')) {
                $doc = Docente::where('id_usuario', $user->id_usuario)->first();
                return $doc?->id_docente;
            }
            $doc = Docente::find($user->id_usuario);
            return $doc?->id_docente;
        }

        // Si no es email, asumimos nro_documento y buscamos en docente.nro_documento
        $doc = Docente::whereRaw('LOWER(nro_documento)=?', [mb_strtolower($ident)])->first();
        return $doc?->id_docente;
    }
}
