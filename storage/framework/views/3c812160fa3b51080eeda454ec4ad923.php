<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['aulas'=>[]]));

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

foreach (array_filter((['aulas'=>[]]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<aside class="side panel">
  <div>
    <div class="text-muted" style="font-weight:700;margin-bottom:6px">
      Aulas (arrástrala para seleccionar)
    </div>
    <div id="aula-list" class="chips">
      <?php $__currentLoopData = $aulas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="chip tool-aula" draggable="true" data-id="<?php echo e($a->id_aula); ?>">
          <?php echo e($a->codigo); ?>

        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
  </div>

  <div class="panel" style="margin-top:12px">
    <div class="text-muted" style="font-weight:700;margin-bottom:6px">Aula activa</div>
    <div id="aula-active" class="active-slot" data-drop="aula">
      <span class="text-muted">Suelta aquí una aula</span>
    </div>
  </div>
</aside>
<?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/components/cu13/aula-palette.blade.php ENDPATH**/ ?>