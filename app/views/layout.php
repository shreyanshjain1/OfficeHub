<?php
declare(strict_types=1);

$user = Auth::currentUser();
$theme = Security::getTheme();

function view_include(string $path, array $vars = []): void { extract($vars); require $path; }

$flashToast = $_SESSION['flash']['toast'] ?? '';
unset($_SESSION['flash']['toast']);

$title = Config::get('APP_NAME') ?: 'Office Request Hub';
?>
<!doctype html>
<html lang="en" data-bs-theme="<?= Security::e($theme) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= Security::e($title) ?></title>
  <link rel="icon" href="/assets/img/logo.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body class="bg-app">
  <?php if ($user): ?>
    <?php view_include(__DIR__ . '/partials/navbar.php', ['user' => $user]); ?>
    <div class="container-fluid">
      <div class="row g-0">
        <div class="col-12 col-lg-2 d-none d-lg-block border-end min-vh-100 bg-sidebar">
          <?php view_include(__DIR__ . '/partials/sidebar.php', ['user' => $user]); ?>
        </div>
        <div class="col-12 col-lg-10">
          <main class="p-3 p-lg-4">
            <?php require $viewFile; ?>
          </main>
        </div>
      </div>
    </div>
  <?php else: ?>
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
      <?php require $viewFile; ?>
    </main>
  <?php endif; ?>

  <?php view_include(__DIR__ . '/partials/toasts.php', ['flashToast' => $flashToast]); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
          crossorigin="anonymous"></script>
  <script src="/assets/js/ui.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
