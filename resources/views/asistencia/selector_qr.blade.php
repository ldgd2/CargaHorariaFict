@extends('layouts.app')

@section('content')
<div class="app-container">
  <div class="card">
    <h1 class="text-xl font-bold mb-2">Generar códigos QR de asistencia</h1>
    <p class="text-muted mb-4">
      Selecciona una carrera, materia y docente para generar el QR que el docente escaneará
      al inicio de su clase.
    </p>

    <!-- Selección de carrera -->
    <!-- Selección de carrera -->
<div class="form-group mb-4">
  <label for="carrera-select">Selecciona una carrera:</label>
  <select id="carrera-select" class="form-control" onchange="loadMaterias(this.value)">
    <option value="">Selecciona una carrera</option>
    @foreach($carreras as $carrera)
      <option value="{{ $carrera->id_carrera }}">{{ $carrera->nombre }}</option>
    @endforeach
  </select>
</div>

<!-- Selección de materia -->
<div class="form-group mb-4" id="materia-container" style="display:none;">
  <label for="materia-select">Selecciona una materia:</label>
  <select id="materia-select" class="form-control" onchange="loadDocentes(this.value)">
    <option value="">Selecciona una materia</option>
  </select>
</div>

<!-- Mostrar docentes -->
<div class="form-group mb-4" id="docentes-container" style="display:none;">
  <label for="docente-select">Selecciona un docente:</label>
  <div id="docentes-list"></div>
</div>

<!-- Tabla de cargas -->
<div class="coor-table-wrap">
  <table class="min-w-full coor-recent" style="width:100%" id="table-cargas">
    <thead>
      <tr>
        <th class="coor-th">Docente</th>
        <th class="coor-th">Aula</th>
        <th class="coor-th">Día</th>
        <th class="coor-th">Horario</th>
        <th class="coor-th" style="width:160px"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-white/5" id="cargas-table-body">
      <!-- Aquí se cargarán las cargas dinámicamente -->
    </tbody>
  </table>
</div>

  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  // 🔹 Cargar materias cuando se selecciona una carrera
  window.loadMaterias = function (carreraId) {
    const materiaContainer  = document.getElementById('materia-container');
    const docentesContainer = document.getElementById('docentes-container');
    const tbody             = document.getElementById('cargas-table-body');
    const materiaSelect     = document.getElementById('materia-select');

    materiaContainer.style.display  = 'none';
    docentesContainer.style.display = 'none';
    tbody.innerHTML = '';

    if (!carreraId) return;

    $.ajax({
      url: `/coordinador/materias/${carreraId}/materias`,
      method: 'GET',
      success: function (data) {
        materiaSelect.innerHTML = '<option value="">Selecciona una materia</option>';

        data.forEach(function (materia) {
          materiaSelect.innerHTML += `<option value="${materia.id_materia}">${materia.nombre}</option>`;
        });

        materiaContainer.style.display = 'block';
      },
      error: function (xhr) {
        console.error('Error al cargar materias:', xhr.responseText);
        alert('Hubo un problema al cargar las materias.');
      }
    });
  }

  // 🔹 Cargar docentes/cargas cuando se selecciona una materia
  window.loadDocentes = function (materiaId) {
    const docentesContainer = document.getElementById('docentes-container');
    const tbody             = document.getElementById('cargas-table-body');

    docentesContainer.style.display = 'none';
    tbody.innerHTML = '';   // limpiamos filas

    if (!materiaId) return;

    $.ajax({
      url: `/coordinador/materias/${materiaId}/docentes`,
      method: 'GET',
      success: function (data) {
        console.log('Docentes/cargas:', data);

        // Día actual en formato ISO: 1 (lunes) - 7 (domingo)
        const today = new Date();
        let jsDay   = today.getDay(); // 0 (domingo) - 6 (sábado)
        const todayIso = jsDay === 0 ? 7 : jsDay;

        // Nombres de días
        const dayNames = {
          1: 'Lunes',
          2: 'Martes',
          3: 'Miércoles',
          4: 'Jueves',
          5: 'Viernes',
          6: 'Sábado',
          7: 'Domingo',
        };

        if (!data.length) {
          tbody.innerHTML = `
            <tr>
              <td colspan="5" class="coor-td text-center text-sm text-muted">
                No hay cargas horarias registradas para esta materia.
              </td>
            </tr>`;
          docentesContainer.style.display = 'block';
          return;
        }

        // 🔹 Ordenar por día (1..7) y luego por hora_inicio
        data.sort(function (a, b) {
          const d1 = Number(a.dia_semana), d2 = Number(b.dia_semana);
          if (d1 !== d2) return d1 - d2;
          return (a.hora_inicio || '').localeCompare(b.hora_inicio || '');
        });

        // 🔹 Armar filas de la tabla
        data.forEach(function (item) {
          const pasaHoy = Number(item.dia_semana) === todayIso;

          const mensajeHoy = pasaHoy
            ? `<div class="text-xs text-emerald-500 mb-1">Este docente pasa clases hoy.</div>`
            : `<div class="text-xs text-red-400 mb-1">Este docente no pasa clases hoy.</div>`;

          const boton = pasaHoy
            ? `<a href="/coordinador/generar-qr/${item.carga_horaria_id}" class="btn btn--primary">
                 Generar QR
               </a>`
            : `<button class="btn btn--primary" disabled>
                 QR no disponible hoy
               </button>`;

          tbody.innerHTML += `
            <tr>
              <td class="coor-td">
                <div class="font-semibold">${item.docente_nombre}</div>
              </td>
              <td class="coor-td">
                ${item.aula_nombre}
              </td>
              <td class="coor-td">
                ${dayNames[Number(item.dia_semana)] ?? ''}
              </td>
              <td class="coor-td">
                ${item.hora_inicio} - ${item.hora_fin}
              </td>
              <td class="coor-td">
                ${mensajeHoy}
                <div class="mt-1">
                  ${boton}
                </div>
              </td>
            </tr>
          `;
        });

        docentesContainer.style.display = 'block';
      },
      error: function (xhr) {
        console.error('Error al cargar docentes:', xhr.responseText);
        alert('Hubo un problema al cargar los docentes.');
      }
    });
  }
</script>
@endsection
