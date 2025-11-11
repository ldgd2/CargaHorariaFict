@extends('layouts.app')
@section('title','Importación masiva')

@section('content')
<div class="app-container">
  <div class="card">
    <h2 class="appbar__title" style="margin:0">Importación CSV/XLSX</h2>
    <p class="text-muted">Soporta varias hojas en un mismo archivo. Se detecta por el <strong>nombre de la hoja</strong>.</p>
  </div>

  @if (session('ok'))
    <div class="snackbar snackbar--ok">{!! session('ok') !!}</div>
  @endif
  @if ($errors->any())
    <div class="snackbar snackbar--error">
      <ul style="margin:0 0 0 18px;">
      @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <form method="post" action="{{ route('admin.import.run') }}" enctype="multipart/form-data" style="display:grid;gap:12px">
      @csrf
      <div class="field">
        <label class="field__label">Archivo Excel</label>
        <div class="field__box">
          <input type="file" name="archivo" class="field__input" accept=".xlsx,.xls" required>
        </div>
        <small class="field__hint">Hojas esperadas: USUARIOS, CARRERAS, AULAS, PERIODOS, MATERIAS, GRUPOS, CARGA_HORARIA.</small>
      </div>
      <button class="btn btn--primary" type="submit">Importar</button>
    </form>
  </div>
</div>
@endsection
