<?php $__env->startSection('title', 'Asistencia Marcada'); ?>

<?php $__env->startSection('content'); ?>
<style>
  .asist-shell{
    max-width: 560px;
    margin: 40px auto;
    padding: 28px 24px 32px;
    border-radius: var(--radius-l);
    background: var(--color-surface);
    border: 1px solid var(--color-outline-variant);
    box-shadow: var(--shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  /* Fondos según estado visual */
  .asist-shell--ok::before,
  .asist-shell--ok::after{
    content:"";
    position:absolute;
    border-radius:999px;
    background: conic-gradient(
      from 0deg,
      #00e5a8 0deg,
      #4ade80 80deg,
      #38bdf8 160deg,
      #a855f7 240deg,
      #f97316 320deg,
      #00e5a8 360deg
    );
    opacity:.16;
    filter:blur(6px);
    animation: asistConfetti 18s linear infinite;
  }
  .asist-shell--ok::before{
    width:220px;height:220px;
    top:-80px;left:-60px;
  }
  .asist-shell--ok::after{
    width:260px;height:260px;
    bottom:-120px;right:-90px;
    animation-direction:reverse;
  }

  .asist-shell--warn{
    background: radial-gradient(circle at top,#92400e33, var(--color-surface));
  }
  .asist-shell--bad{
    background: radial-gradient(circle at top,#7f1d1d33, var(--color-surface));
  }

  @keyframes asistConfetti{
    0%{transform:translate3d(0,0,0) rotate(0deg);}
    50%{transform:translate3d(8px,10px,0) rotate(90deg);}
    100%{transform:translate3d(0,0,0) rotate(180deg);}
  }

  .asist-icon-wrap{
    position:relative;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:92px;height:92px;
    border-radius:999px;
    margin-bottom:10px;
  }
  .asist-icon-ring{
    position:absolute;
    inset:-6px;
    border-radius:999px;
    border:2px solid transparent;
    animation: asistPulse 2.4s ease-out infinite;
  }
  .asist-icon-core{
    position:relative;
    width:72px;height:72px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:40px;
  }

  @keyframes asistPulse{
    0%{transform:scale(.9);opacity:.4;}
    50%{transform:scale(1.05);opacity:.12;}
    100%{transform:scale(1.15);opacity:0;}
  }

  .asist-shell--ok .asist-icon-ring{
    border-color:rgba(16,185,129,.45);
  }
  .asist-shell--ok .asist-icon-core{
    background:radial-gradient(circle at 30% 0,#6ee7b7,#22c55e);
    color:#00130e;
  }
  .asist-shell--warn .asist-icon-ring{
    border-color:rgba(234,179,8,.55);
  }
  .asist-shell--warn .asist-icon-core{
    background:radial-gradient(circle at 30% 0,#fbbf24,#f97316);
    color:#1f1300;
  }
  .asist-shell--bad .asist-icon-ring{
    border-color:rgba(248,113,113,.6);
  }
  .asist-shell--bad .asist-icon-core{
    background:radial-gradient(circle at 30% 0,#fb7185,#ef4444);
    color:#130002;
  }

  .asist-shell--ok .asist-icon-core{
    animation: asistPopIn 600ms var(--curve-ease);
  }
  .asist-shell--warn .asist-icon-core{
    animation: asistClock 1200ms ease-in-out infinite;
  }
  .asist-shell--bad .asist-icon-core{
    animation: asistShake 680ms cubic-bezier(.36,.07,.19,.97) both;
  }

  @keyframes asistPopIn{
    0%{transform:scale(.4) rotate(-14deg);opacity:0;}
    60%{transform:scale(1.08) rotate(4deg);opacity:1;}
    100%{transform:scale(1) rotate(0deg);opacity:1;}
  }
  @keyframes asistClock{
    0%{transform:rotate(0deg);}
    25%{transform:rotate(8deg);}
    50%{transform:rotate(-6deg);}
    75%{transform:rotate(4deg);}
    100%{transform:rotate(0deg);}
  }
  @keyframes asistShake{
    10%,90%{transform:translate3d(-1px,0,0);}
    20%,80%{transform:translate3d(2px,0,0);}
    30%,50%,70%{transform:translate3d(-4px,0,0);}
    40%,60%{transform:translate3d(4px,0,0);}
  }

  .asist-title{
    font-size:1.6rem;
    font-weight:800;
    margin:0 0 .35rem 0;
  }
  .asist-sub{
    margin:0;
    color:var(--color-on-surface-variant);
    font-size:.96rem;
  }

  .asist-pill{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border-radius:999px;
    padding:6px 14px;
    font-size:.9rem;
    font-weight:700;
    margin-top:14px;
    margin-bottom:6px;
  }
  .asist-pill--ok{
    background:rgba(16,185,129,.14);
    color:#6ee7b7;
    border:1px solid rgba(16,185,129,.5);
  }
  .asist-pill--warn{
    background:rgba(234,179,8,.16);
    color:#fde68a;
    border:1px solid rgba(234,179,8,.55);
  }
  .asist-pill--bad{
    background:rgba(248,113,113,.18);
    color:#fecaca;
    border:1px solid rgba(248,113,113,.65);
  }

  .asist-detail{
    margin-top:4px;
    font-size:.9rem;
    color:var(--color-on-surface-variant);
  }

  .asist-grid{
    margin-top:20px;
    display:grid;
    gap:10px;
  }
  @media (min-width: 720px){
    .asist-grid{
      grid-template-columns: repeat(2,minmax(0,1fr));
    }
  }

  .asist-card{
    border-radius: var(--radius-s);
    border:1px solid var(--color-outline-variant);
    background:rgba(15,23,42,.68);
    padding:10px 12px;
    text-align:left;
  }
  .asist-card-label{
    font-size:.75rem;
    letter-spacing:.04em;
    text-transform:uppercase;
    color:var(--color-on-surface-variant);
    margin-bottom:2px;
    font-weight:700;
  }
  .asist-card-value{
    font-size:.95rem;
    font-weight:600;
  }

  .asist-actions{
    margin-top:22px;
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:10px;
  }
</style>

<?php
  $mood = $mood ?? 'ok';
    // Variables esperadas desde el controlador
    $fechaSesion = $fechaSesion ?? 'No disponible';  // Asegúrate de que la variable exista
    $estado = $estado ?? 'Presente';  // Asegúrate de que esta variable también esté definida
    $horaProgramada = $horaProgramada ?? '—';  // Definir valores predeterminados si no están presentes
?>

<div class="asist-shell asist-shell--<?php echo e($mood); ?>">
    <div class="asist-icon-wrap">
        <div class="asist-icon-ring"></div>
        <div class="asist-icon-core">
            <?php if($mood === 'ok'): ?>
                 ✓
            <?php elseif($mood === 'warn'): ?>
                 ⏰
            <?php else: ?>
                 !
            <?php endif; ?>
        </div>
    </div>

    <h1 class="asist-title">
        Asistencia marcada
    </h1>
    <p class="asist-sub">
        Hola, <strong><?php echo e($docenteNombre ?? 'Docente'); ?></strong>.
        Tu asistencia para hoy ha sido registrada.
    </p>

    <div class="asist-pill asist-pill--<?php echo e($mood); ?>">
        <?php echo e($estado ?? 'Asistencia en horario'); ?>

    </div>
    <p class="asist-detail">
        <?php echo e($detalleTiempo ?? 'Información no disponible'); ?>

    </p>

    <div class="asist-grid">
        <div class="asist-card">
            <div class="asist-card-label">Materia · Grupo</div>
            <div class="asist-card-value">
                <?php echo e($materia ?? 'Materia no disponible'); ?> — <?php echo e($grupo ?? 'Grupo no disponible'); ?>

            </div>
        </div>
        <div class="asist-card">
            <div class="asist-card-label">Aula</div>
            <div class="asist-card-value">
                <?php echo e($aula ?? 'Aula no disponible'); ?>

            </div>
        </div>
        <div class="asist-card">
            <div class="asist-card-label">Fecha de sesión</div>
            <div class="asist-card-value">
                <?php echo e(\Illuminate\Support\Carbon::parse($fechaSesion)->format('d/m/Y')); ?>

            </div>
        </div>
        <div class="asist-card">
            <div class="asist-card-label">Horario</div>
            <div class="asist-card-value">
                Programado: <?php echo e($horaProgramada); ?> · Marcado: <?php echo e($horaMarcado); ?>

            </div>
        </div>
        <div class="asist-card">
            <div class="asist-card-label">Estado registrado</div>
            <div class="asist-card-value">
                <?php echo e($estado); ?>

            </div>
        </div>
        <div class="asist-card">
            <div class="asist-card-label">Tipo de registro</div>
            <div class="asist-card-value">
                QR automático
            </div>
        </div>
    </div>

    <div class="asist-actions">
        <?php if(Route::has('docente.dashboard')): ?>
            <a href="<?php echo e(route('docente.dashboard')); ?>" class="btn btn--primary">
                Volver al panel del docente
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn--text" onclick="history.back()">
            Marcar otra sesión
        </button>
    </div>
</div>


<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\ldgd2\OneDrive\Documentos\Universidad\si1\Examen\CargaHorariaFict\resources\views/asistencia/marcada.blade.php ENDPATH**/ ?>