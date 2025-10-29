<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PeriodoAcademico extends Model
{
    protected $table = 'periodo_academico';
    protected $primaryKey = 'id_periodo';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'fecha_inicio', 'fecha_fin', 'estado_publicacion', 'activo'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activo'       => 'boolean',
    ];

    /** Vigentes: activos o publicados (bloquean solapamiento) */
    public function scopeVigentes(Builder $q): Builder
    {
        return $q->where(function($q){
            $q->where('activo', true)->orWhere('estado_publicacion', 'publicado');
        });
    }

    /** ¿Hay solapamiento con otros vigentes? */
    public static function haySolapamiento(string $ini, string $fin, ?int $exceptId = null): bool
    {
        return static::vigentes()
            ->when($exceptId, fn($q) => $q->where('id_periodo','!=',$exceptId))
            ->where(function($q) use ($ini,$fin){
                $q->whereBetween('fecha_inicio', [$ini,$fin])
                  ->orWhereBetween('fecha_fin',   [$ini,$fin])
                  ->orWhere(function($qq) use ($ini,$fin){
                      $qq->where('fecha_inicio','<=',$ini)->where('fecha_fin','>=',$fin);
                  });
            })
            ->exists();
    }

    /** Nombre único (case-insensitive) */
    public static function nombreUsado(string $nombre, ?int $exceptId = null): bool
    {
        return static::when($exceptId, fn($q) => $q->where('id_periodo','!=',$exceptId))
            ->whereRaw('LOWER(nombre) = LOWER(?)', [$nombre])
            ->exists();
    }

    public function stats()
{
    $q = PeriodoAcademico::query();

    
    return response()->json([
        'total'      => (clone $q)->count(),
        'borrador'   => (clone $q)->where('estado_publicacion', 'borrador')->count(),
        'activo'     => (clone $q)->where('estado_publicacion', 'activo')->count(),
        'publicado'  => (clone $q)->where('estado_publicacion', 'publicado')->count(),
        'archivado'  => (clone $q)->where('estado_publicacion', 'archivado')->count(),
    ]);
}
}
