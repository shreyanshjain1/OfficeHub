<?php
declare(strict_types=1);
?>
<div class="card border-0 shadow-sm">
  <div class="card-body p-4 p-lg-5 text-center">
    <div class="display-6 mb-2">🗂️</div>
    <h5 class="fw-semibold mb-2"><?= Security::e($title) ?></h5>
    <p class="text-muted mb-4"><?= Security::e($subtitle) ?></p>
    <?= $actionHtml ?>
  </div>
</div>
