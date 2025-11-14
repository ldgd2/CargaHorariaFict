<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Collection;

/**
 * Clase de exportación para el reporte de Carga Horaria.
 * Implementa FromView para usar una plantilla Blade como estructura del Excel.
 */
class CargaHorariaExport implements FromView
{
    protected Collection $datos;
    protected array $filtros;

    public function __construct(Collection $datos, array $filtros = [])
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function view(): View
    {
        // Retorna la vista Blade que contiene la estructura del reporte.
        // Asegúrate de que esta vista contenga una tabla HTML válida.
        return view('reportes.cargahorariaexport', [
            'datos' => $this->datos,
            'filtros' => $this->filtros
        ]);
    }
}