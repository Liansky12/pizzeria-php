<?php
require_once './funciones.php';
session_start();
controlUsuario();
if (!esAdmin($_SESSION['control']))
  header('Location: ./index.php');

if (isset($_GET['accion']) && !is_numeric($_GET['accion'])) {
  setcookie("msg[error]", "Acción inválida", time() + 2);
  header('Location: ./gestion.php');
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
    <h1 class="text-3xl font-bold mb-6">Gestión pizzería</h1>
    <div class="flex flex-wrap gap-4">
      <section class="w-max h-max p-5 rounded-md bg-gray-200 sticky top-6">
        <ul class="space-y-3">
          <li>
            <span class="font-bold text-lg block text-gray-700">Ingredientes</span>
            <ul class="pl-5 space-y-1">
              <li><a href="./gestion.php?accion=1&subaccion=1"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Listar ingredientes</a></li>
              <li><a href="./gestion.php?accion=1&subaccion=2"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Añadir un nuevo
                  ingrediente</a></li>
              <li><a href="./gestion.php?accion=1&subaccion=3"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Actualizar un ingrediente</a>
              </li>
              <li><a href="./gestion.php?accion=1&subaccion=4"
                  class="block text-gray-600 hover:text-red-500 transition duration-300">Eliminar un ingrediente</a>
              </li>
            </ul>
          </li>
          <li>
            <span class="font-bold text-lg block text-gray-700">Estadísticas</span>
            <ul class="pl-5 space-y-1">
              <li><a href="./gestion.php?accion=2&subaccion=1"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Ingredientes más escogidos</a>
              </li>
              <li><a href="./gestion.php?accion=2&subaccion=2"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Bases de pizza más
                  escogidas</a></li>
              <li><a href="./gestion.php?accion=2&subaccion=3"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Clientes con más pizzas
                  encargadas</a></li>
            </ul>
          </li>
          <li>
            <span class="font-bold text-lg block text-gray-700">Clientes/Usuarios</span>
            <ul class="pl-5 space-y-1">
              <li><a href="./gestion.php?accion=3&subaccion=1"
                  class="block text-gray-600 hover:text-blue-500 transition duration-300">Usuarios registrados</a></li>
            </ul>
          </li>
        </ul>
      </section>

      <?php if (isset($_GET['accion'])) {
        ?>
        <section class="p-5 flex-grow rounded-md border-solid border-2 border-gray-200">
          <?php
          renderGestion();
          ?>
        </section>
        <?php
      } ?>
    </div>
    <?php
    if (isset($_COOKIE['msg']) || isset($GLOBALS['msg'])) {
      renderMsg(isset($_COOKIE['msg']) ? $_COOKIE['msg'] : $GLOBALS['msg']);
      if (isset($GLOBALS['msg']))
        unset($GLOBALS['msg']);
    }
    ?>
  </main>
</body>

</html>