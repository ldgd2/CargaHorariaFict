<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
  'periodos'=>[], 'docentes'=>[], 'aulas'=>[],
  'gridUrl'=>'#', 'checkUrl'=>'#', 'dragUrl'=>'#',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
  'periodos'=>[], 'docentes'=>[], 'aulas'=>[],
  'gridUrl'=>'#', 'checkUrl'=>'#', 'dragUrl'=>'#',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div id="cu13-filters"
     class="card toolbar"
     data-url-grid="<?php echo e($gridUrl); ?>"
     data-url-check="<?php echo e($checkUrl); ?>"
     data-url-drag="<?php echo e($dragUrl); ?>">
  <div class="filters">
    <div>
      <label class="text-muted">Período</label>
      <select id="f-per" class="field__input">
        <?php $__currentLoopData = $periodos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($p->id_periodo); ?>"><?php echo e($p->nombre); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div>
      <label class="text-muted">Docente (opcional)</label>
      <select id="f-doc" class="field__input">
        <option value="">— Todos —</option>
        <?php $__currentLoopData = $docentes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($d->id_docente); ?>"><?php echo e($d->nombre_completo); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>

    <div>
      <label class="text-muted">Aula (opcional)</label>
      <select id="f-aula" class="field__input">
        <option value="">— Todas —</option>
        <?php $__currentLoopData = $aulas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($a->id_aula); ?>"><?php echo e($a->codigo); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </select>
    </div>
  </div>

  <div style="display:flex;justify-content:flex-end;align-items:end">
    <button id="btn-load" class="btn btn--primary">Cargar</button>
  </div>
</div>
<?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/components/cu13/filters.blade.php ENDPATH**/ ?>