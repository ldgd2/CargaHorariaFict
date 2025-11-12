<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView; // <-- USAMOS FromView
use Illuminate\Support\Collection;

/**
 * Utiliza FromView para usar una plantilla Blade y asegurar que el formato sea idéntico
 * al PDF, incluyendo los metadatos de encabezado y filtros.
 */
class AsistenciaExport implements FromView
{
    protected Collection $datos; // Cambiado a $datos para consistencia
    protected array $filtros;

    /**
     * Constructor que recibe los datos y un array de filtros aplicados.
     * @param Collection $datos Colección de datos del reporte.
     * @param array $filtros Array con los filtros aplicados (ej: ['periodo_id' => 'all', ...])
     */
    public function __construct(Collection $datos, array $filtros = [])
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    /**
     * Retorna la vista Blade que contiene la estructura del reporte.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        // Retorna la vista Blade que contiene la estructura del reporte.
        // Pasamos los datos y los filtros a la vista.
        return view('usuarios.admin.admin.reportes.asistenciaexport', [
            'datos' => $this->datos,
            'filtros' => $this->filtros
        ]);
    }
}