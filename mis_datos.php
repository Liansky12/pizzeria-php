<?php
require_once './funciones.php';
session_start();
controlUsuario();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nuevosDatos = verificarDatos($_POST, 'update');
  if ($nuevosDatos['resp']) {
    accionUsuario($nuevosDatos['nuevosDatos'], 'update', $_SESSION['control']);
  } else
    $GLOBALS['msg']['error'] = $nuevosDatos['errores'];
}
if (!empty($_GET['delete'])) {
  accionUsuario(null, 'delete', $_SESSION['control']);
  header('Location: ./salir.php');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Mis datos</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col">
  <?php renderCabecera(); ?>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Mis datos</h1>
    <div class="flex flex-col gap-6 md:grid md:grid-flow-col">
      <?php
      renderMisDatos($_SESSION['control']);
      ?>
    </div>
    <?php
    if (isset($_COOKIE['msg']) || isset($GLOBALS['msg'])) {
      renderMsg($_COOKIE['msg'] ?? $GLOBALS['msg']);
      if (isset($GLOBALS['msg']))
        unset($GLOBALS['msg']);
    }
    ?>
  </main>
</body>

</html>