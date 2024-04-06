<?php
session_start();
if (empty($_SESSION['control']))
  header('Location: ./iniciar_sesion.php');

require_once './funciones.php';
if (!empty($_SESSION['control']) && esAdmin($_SESSION['control']))
  header('Location: ./index.php');

function renderElaborar()
{
  if (empty($_SESSION['control']))
    return "<p>Para elaborar una pizza debes iniciar sesión antes</p>";
  if (isset($_GET['cancelar'])) {
    unset($_SESSION['pedido']);
    header('Location: ./index.php');
  }
  if (empty($_GET['pos']) && isset($_SESSION['pedido']['nombrePedido']))
    header('Location: ./elaborar.php?pos=1');
  if (isset($_GET['delete']) && is_numeric($_GET['delete']))
    unset($_SESSION['pedido']['pizzas'][$_GET['delete']]);
  if (($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_SESSION['pedido'])) && isset($_GET['pos'])) {
    switch ($_GET['pos']) {
      case 'add':
        renderFormularioPizza();
        break;
      case '1':
        echo "<h2 class='text-xl font-bold mb-2'>Gestionar pizzas</h2>";
        if (isset($_POST['nombrePedido']))
          $_SESSION['pedido']['nombrePedido'] = trim(filter_var($_POST['nombrePedido'], FILTER_SANITIZE_SPECIAL_CHARS));
        if (!isset($_SESSION['pedido']['pizzas']))
          $_SESSION['pedido']['pizzas'] = [];
        if (isset($_POST['idBase'])) {
          $nuevosDatos = verificarDatos($_POST, 'pizza');
          if ($nuevosDatos['resp']) {
            array_push($_SESSION['pedido']['pizzas'], $nuevosDatos['nuevosDatos']);
            $GLOBALS['msg']['info'] = "Pizza agregada al carrito correctamente";
          } else {
            $GLOBALS['msg']['error'] = $nuevosDatos['errores'];
          }
        }
        echo "<div class='flex flex-col gap-2'>";
        echo "<p><span class='font-bold'>Nombre del encargo: </span>";
        echo !empty($_SESSION['pedido']['nombrePedido']) ? $_SESSION['pedido']['nombrePedido'] : '(sin nombre)';
        echo "</p><p><span class='font-bold'>Pizzas: </span>";
        if (!empty($_SESSION['pedido']['pizzas'])) {
          echo "<table class='border-collapse w-max'>
          <tr>
          <thead>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>#</th>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Nombre pizza</th>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Precio pizza</th>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Cantidad</th>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Precio total</th>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Accion</th>
          </thead>  
          </tr>
          <tbody>
          ";
          foreach ($_SESSION['pedido']['pizzas'] as $key => $value) {
            $datosPedidoPizza = renderPedidoPizza($value);
            $_SESSION['pedido']['pizzas'][$key]['precioTotal'] = $datosPedidoPizza['precioTotal'];
            echo "
            <tr class='bg-white lg:hover:bg-gray-100 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>" . ($key + 1) . "</td>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>" . ($datosPedidoPizza['msg']['nombrePizza'] ?? '(sin nombre)') . "</td>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>{$datosPedidoPizza['msg']['precioTotalPizza']}</td>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>{$_SESSION['pedido']['pizzas'][$key]['cantidad']}</td>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>" . (floatval($datosPedidoPizza['msg']['precioTotalPizza']) * intval($_SESSION['pedido']['pizzas'][$key]['cantidad'])) . "</td>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'><a class='text-red-500' href='./elaborar.php?pos=1&delete=$key'>Eliminar</a></td>
            </tr>
            ";
          }
          echo "</tbody></table>";
        } else {
          echo '(sin pizzas)';
        }
        echo "</p>";
        ?>
        <form action="<?= $_SERVER['PHP_SELF'] . "?pos=2" ?>" method="post">
          <p>
            <button
              class="middle none mt-6 center rounded-lg bg-yellow-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-yellow-500/20 transition-all hover:shadow-lg hover:shadow-yellow-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="button" onclick="location='./elaborar.php?pos=add'">Agregar pizza</button>
            <button
              class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-orange-500/20 transition-all hover:shadow-lg hover:shadow-orange-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="submit">Siguiente</button>
            <button
              class="middle none mt-6 center rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="button" onclick="location='./elaborar.php?cancelar=true'">Cancelar encargo</button>
          </p>
        </form>
        <?php
        break;
      case '2':
        if (empty($_SESSION['pedido']['pizzas'])) {
          setcookie('msg[error]', 'Se necesita pedir una pizza como mínimo', time() + 2);
          header('Location: ./elaborar.php');
        }
        $numeroPizzas = count($_SESSION['pedido']['pizzas']);
        $precioTotal = array_reduce($_SESSION['pedido']['pizzas'], function ($prev, $curr) {
          return $prev + $curr['precioTotal'] * intval($curr['cantidad']);
        }, 0);
        echo "<h2 class='text-xl font-bold mb-2'>Resumen</h2>";
        echo "<p class='font-bold'>Número de pizzas: $numeroPizzas</p>";
        echo "<p class='font-bold'>Precio total: €$precioTotal</p>";
        $_SESSION['pedido']['precioTotal'] = $precioTotal;
        $_SESSION['pedido']['numeroPizzas'] = $numeroPizzas;
        ?>
        <form action="<?= $_SERVER['PHP_SELF'] . "?pos=3" ?>" method="post">
          <p>
            <button
              class="middle none mt-6 center rounded-lg bg-gray-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-gray-500/20 transition-all hover:shadow-lg hover:shadow-gray-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="button" onclick="location='./elaborar.php?pos=1'">Anterior</button>
            <button
              class="middle none mt-6 center rounded-lg bg-blue-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-blue-500/20 transition-all hover:shadow-lg hover:shadow-blue-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="submit">Ordenar</button>
            <button
              class="middle none mt-6 center rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
              data-ripple-light="true" type="button" onclick="location='./elaborar.php?cancelar=true'">Cancelar encargo</button>
          </p>
        </form>
        <?php
        break;
      case '3':
        if (empty($_SESSION['pedido']['pizzas']))
          header('Location: ./elaborar.php');
        $resultado = procesarPedido($_SESSION['pedido'], $_SESSION['control']);
        echo "<h2 class='text-xl font-bold mb-2'>Confirmación</h2>";
        if ($resultado['resp'] === true) {
          echo "<p>¡Gracias por pedir tus pizzas!</p>";
          ?>
          <img class="w-48 mt-8 rounded-lg" src="./imagenes/pizza.gif" alt="Pizza confirmada">
          <?php
        } else {
          echo "<p>Parece que ha habido algún problema: {$resultado['error']}</p>";
        }
        unset($_SESSION['pedido']);
        ?>
        <button
          class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-orange-500/20 transition-all hover:shadow-lg hover:shadow-orange-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          data-ripple-light="true" type="button" onclick="location='./elaborar.php'">Realizar otro encargo</button>
        <button
          class="middle none mt-6 center rounded-lg bg-gray-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-gray-500/20 transition-all hover:shadow-lg hover:shadow-gray-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          data-ripple-light="true" type="button" onclick="location='./index.php'">Ir al Inicio</button>
        <?php
        break;
    }
  } else {
    ?>
    <form class="w-full max-w-lg" action="<?= $_SERVER['PHP_SELF'] . "?pos=1" ?>" method="post">
      <p>
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombrePedido">
          Nombre encargo:
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="(opcional)" name="nombrePedido" value="">
      </p>
      <p>
        <button
          class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-orange-500/20 transition-all hover:shadow-lg hover:shadow-orange-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          data-ripple-light="true">
          Siguiente
        </button>
      </p>
    </form>
    <?php
  }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Elaborar pizza</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col">
  <?php renderCabecera() ?>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Nuevo encargo de pizzas</h1>
    <?= renderElaborar(); ?>
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