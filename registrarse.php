<?php
require_once './funciones.php';
session_start();
if (isset($_SESSION['control']) && !esAdmin($_SESSION['control']))
  header('Location: ./index.php');

function renderRegistro()
{
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
      if (existeUsuario($_POST['uUsuario']))
        throw new RuntimeException("Ya existe un usuario registrado con el nombre de usuario introducido");
      $datos = verificarDatos($_POST, 'signin');
      if (!$datos['resp'])
        throw new RuntimeException("Los datos son inválidos: {$datos['errores']}");
      accionUsuario($datos['nuevosDatos'], 'insert');
      header('Location: ./index.php');
    } catch (RuntimeException $e) {
      $GLOBALS['msg']['error'] = $e->getMessage();
      renderFormularioRegistro($_POST);
    }
  } else {
    renderFormularioRegistro();
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Registrarse</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col">
  <?php renderCabecera(); ?>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Registro</h1>
    <?= renderRegistro(); ?>
    <section>
      <?php
      if (!empty($GLOBALS['msg'])) {
        renderMsg($GLOBALS['msg']);
        unset($GLOBALS['msg']);
      }
      ?>
    </section>
  </main>
</body>

</html>