<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Collection;

/**
 * Clase de exportación para el reporte de Horarios de Aula.
 * Implementa FromView para usar una plantilla Blade como estructura del Excel.
 */
class HorariosAulaExport implements FromView
{
    protected Collection $datos;

    public function __construct(Collection $datos, array $filtros = [])
    {
        $this->datos = $datos;
        $this->filtros = $filtros;
    }

    public function view(): View
    {
        // Retorna la vista Blade que contiene la estructura del reporte.
        // Asegúrate de que esta vista contenga una tabla HTML válida.
        return view('usuarios.admin.admin.reportes.horariosaulaexport', [
            'datos' => $this->datos,
            'filtros' => $this->filtros
        ]);
    }
}