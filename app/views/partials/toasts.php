<?php
declare(strict_types=1);
?>
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
  <div id="appToast" class="toast align-items-center text-bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="appToastBody">Ready.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<script>
  window.__FLASH_TOAST__ = <?= json_encode($flashToast ?: '', JSON_UNESCAPED_SLASHES) ?>;
</script>
