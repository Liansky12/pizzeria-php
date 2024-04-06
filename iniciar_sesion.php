<?php
session_start();
if (!empty($_SESSION['control']))
  header('Location: ./index.php');

require_once './funciones.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $datos = verificarDatos($_POST, 'login');
    if (!$datos['resp'])
      throw new RuntimeException($datos['errores']);
    $_SESSION['control'] = $datos['nuevosDatos'];
    header('Location: ./index.php');
  } catch (RuntimeException $e) {
    $GLOBALS['msg']['error'] = "Credenciales incorrectas. {$e->getMessage()}";
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Iniciar sesión</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col ">
  <?php renderCabecera(); ?>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Iniciar sesión</h1>
    <?php
    renderFormularioLogin($_POST ?? []);
    if (isset($_COOKIE['msg']) || isset($GLOBALS['msg'])) {
      renderMsg($_COOKIE['msg'] ?? $GLOBALS['msg']);
      if (isset($GLOBALS['msg']))
        unset($GLOBALS['msg']);
    }
    ?>
  </main>
</body>

</html>