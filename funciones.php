<?php
function conexion($base_datos = null)
{
  try {
    $conexion = ($base_datos !== null)
      ? @new mysqli($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $base_datos)
      : @new mysqli($_ENV['DB_HOST'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);

    if ($conexion->connect_error) {
      throw new RuntimeException($conexion->connect_error);
    }
    return $conexion;
  } catch (RuntimeException) {
    return null;
  }
}

function controlUsuario()
{
  if (empty($_SESSION['control']))
    header('Location: ./index.php');
}

function existeUsuario($uUsuario)
{
  $existeUsuario = false;
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if (!$conexion)
      throw new RuntimeException();
    $stmt = $conexion->stmt_init();
    if (!$stmt->prepare("SELECT `uUsuario` FROM `usuario` WHERE `uUsuario` = ?"))
      throw new RuntimeException();
    if (!$stmt->bind_param("s", $uUsuario))
      throw new RuntimeException();
    if (!$stmt->execute())
      throw new RuntimeException();
    $existeUsuario = $stmt->get_result()->num_rows > 0;
    if (!$existeUsuario)
      throw new RuntimeException();
    $stmt->close();
  } catch (RuntimeException) {
    $existeUsuario = false;
  } finally {
    try {
      if ($conexion !== null) {
        if (!$conexion->close())
          throw new RuntimeException();
      }
    } catch (RuntimeException) {
      // error al cerrar la conexión
    }
  }
  return $existeUsuario;
}

/**
 * Función que devuelve un array con los nuevos datos sanitizados
 * @param array $datos los datos a comprobar
 * @param "login"|"signin"|"update"|"pizza" $accion la acción de donde provienen los datos
 * @return array devuelve un array asociativo. 'resp' contiene true si los datos son correcto y 
 * 'nuevosDatos' es un array con los datos sanitizados. En caso de error, la clave 'errores' contendrá
 * un string con el error en cuestión.
 */
function verificarDatos($datos, $accion)
{
  /* 
  Regex para contraseñas más robustas:
  "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{5,32}$/" 
  */
  $regex = [
    'nombreReal' => "/^[\wáÁéÉíÍóÓúÚñÑçÇ\d\s\,\.]{0,200}$/",
    'uUsuario' => "/^[\w\d\_\-]{3,100}$/",
    'passwd' => "/^.{5,32}$/",
    'direccion' => "/^[\wáÁéÉíÍóÓúÚñÑçÇ\d\s\,\.]{0,200}$/",
    'telefono' => "/^[\d]{0,12}$/",
    'tipoUsuario' => "/^[\w]{1,10}$/",
    'nombrePizza' => "/^[\wáÁéÉíÍóÓúÚñÑçÇ\d\s\#\,\.\_\-]{0,50}$/",
    'idBase' => "/^[\d]{1,10}$/"
  ];
  $errores = null;
  switch ($accion) {
    case 'login':
      $conexion = conexion($_ENV['DB_DATABASE']);
      try {
        /**
         * Aquí se va a permitir iniciar sesión sin verificar el contenido con una expresión regular
         * para permitir al usuario iniciar sesión con su nombre de usuario si tiene algún espacio al final
         * o al inicio de la cadena
         * if (!preg_match($regex['uUsuario'], $datos['uUsuario']))
         * throw new RuntimeException("El nombre de usuario no es válido"); 
         */
        $uUsuario = trim(filter_var($datos['uUsuario'], FILTER_SANITIZE_SPECIAL_CHARS));
        if (!$conexion)
          throw new RuntimeException("Error al establecer la conexión con la base de datos");
        $stmt = $conexion->stmt_init();
        if (!$stmt->prepare("SELECT `idUsuario`, `passwd` FROM `usuario` WHERE `uUsuario` = ?"))
          throw new RuntimeException("No se pudo iniciar sesión (#1)");
        if (!$stmt->bind_param("s", $uUsuario))
          throw new RuntimeException("No se pudo iniciar sesión (#2)");
        if (!$stmt->execute())
          throw new RuntimeException("No se pudo iniciar sesión (#3)");
        $usuario = $stmt->get_result();
        if ($usuario->num_rows == 0)
          throw new RuntimeException("El usuario '$uUsuario' no está registrado");
        $usuario = $usuario->fetch_assoc();
        if (!password_verify($datos['passwd'], $usuario['passwd']))
          // Contraseña incorrecta
          throw new RuntimeException();
        return ['resp' => true, 'nuevosDatos' => $usuario['idUsuario']];
      } catch (RuntimeException $e) {
        $errores = $e->getMessage();
      } finally {
        try {
          if ($conexion !== null)
            $conexion->close();
        } catch (\Throwable) {
          // error al cerrar la conexión
        }
      }
      break;
    case 'signin':
      $nuevosDatos = [];
      try {
        foreach ($datos as $key => $value) {
          if (!preg_match($regex[$key], $value))
            throw new RuntimeException("Dato inválido en el campo '$key'");
          $nuevosDatos[$key] = trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }
        return ['resp' => true, 'nuevosDatos' => $nuevosDatos];
      } catch (RuntimeException $e) {
        $errores = $e->getMessage();
      }
      break;
    case 'update':
      $nuevosDatos = [];
      try {
        foreach ($datos as $key => $value) {
          if (!preg_match($regex[$key], $value))
            throw new RuntimeException("Dato inválido en el campo '$key'");
          $nuevosDatos[$key] = trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }
        return ['resp' => true, 'nuevosDatos' => $nuevosDatos];
      } catch (RuntimeException $e) {
        $errores = $e->getMessage();
      }
      break;
    case 'pizza':
      $nuevosDatos = [];
      try {
        foreach ($datos as $key => $value) {
          if ($key == 'ingredientes') {
            foreach ($value as $idIngrediente => $cantidadIngrediente) {
              if (!empty($cantidadIngrediente)) {
                $nuevaCantidadIngrediente = intval(trim(filter_var($cantidadIngrediente, FILTER_SANITIZE_NUMBER_INT)));
                if ($nuevaCantidadIngrediente < 0 || $nuevaCantidadIngrediente > 200)
                  throw new RuntimeException("Dato inválido en el campo del ingrediente (ID: $idIngrediente)");
                $nuevosDatos[$key][$idIngrediente] = $nuevaCantidadIngrediente;
              }
            }
            continue;
          }
          if ($key == 'cantidad') {
            if (empty($value)) {
              $nuevosDatos[$key] = 1;
            } else {
              $nuevaCantidad = intval(trim(filter_var($value, FILTER_SANITIZE_NUMBER_INT)));
              if ($nuevaCantidad < 1 || $nuevaCantidad > 200)
                throw new RuntimeException("Dato inválido en el campo '$key'");
              $nuevosDatos[$key] = $nuevaCantidad;
            }
            continue;
          }
          if (!preg_match($regex[$key], $value))
            throw new RuntimeException("Dato inválido en el campo '$key'");
          $nuevosDatos[$key] = trim(filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS));
        }
        if (empty($nuevosDatos['ingredientes']))
          throw new RuntimeException('Debes seleccionar al menos un ingrediente');
        return ['resp' => true, 'nuevosDatos' => $nuevosDatos];
      } catch (RuntimeException $e) {
        $errores = $e->getMessage();
      }
      break;
  }
  return ['resp' => false, 'nuevosDatos' => [], 'errores' => $errores];
}

function accionUsuario($datos, $accion, $idUsuario = null)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if (!$conexion)
      throw new RuntimeException("Conexión fallida con la base de datos");

    $stmt = $conexion->stmt_init();
    if (!$stmt)
      throw new RuntimeException("No se pudo iniciar la sentencia preparada");

    switch ($accion) {
      case 'insert':
        if (!$stmt->prepare("INSERT INTO `usuario` VALUES (NULL, ?, ?, ?, ?, ?, ?, DEFAULT)"))
          throw new RuntimeException("No se pudo preparar la sentencia");

        $tipoUsuario = $datos['tipoUsuario'] ?? 'cliente';
        $passwd = password_hash($datos['passwd'], PASSWORD_DEFAULT);

        if (
          !$stmt->bind_param(
            "ssssss",
            $datos['uUsuario'],
            $passwd,
            $datos['nombreReal'],
            $datos['direccion'],
            $datos['telefono'],
            $tipoUsuario
          )
        )
          throw new RuntimeException("Error al asignar los parámetros");

        if (!$stmt->execute())
          throw new RuntimeException("Error al registrar al usuario '{$datos['uUsuario']}'");

        setcookie("msg[info]", "Usuario registrado correctamente $tipoUsuario", time() + 2);
        break;

      case 'update':
        if (
          !$stmt->prepare("
          UPDATE `usuario` 
          SET `nombreReal` = ?, `passwd` = ?, `direccion` = ?, `telefono` = ? 
          WHERE idUsuario = ?")
        )
          throw new RuntimeException("No se pudo preparar la sentencia");
        $passwd = password_hash($datos['passwd'], PASSWORD_DEFAULT);
        if (
          !$stmt->bind_param(
            "ssssi",
            $datos['nombreReal'],
            $passwd,
            $datos['direccion'],
            $datos['telefono'],
            $idUsuario
          )
        )
          throw new RuntimeException("Error al asignar los parámetros");

        if (!$stmt->execute())
          throw new RuntimeException("Error al actualizar los datos del usuario '{$datos['uUsuario']}'");

        $GLOBALS['msg']['info'] = 'Datos de usuario actualizados correctamente';
        break;

      case 'delete':
        if (!$stmt->prepare("DELETE FROM `usuario` WHERE `idUsuario` = ?"))
          throw new RuntimeException("No se pudo preparar la sentencia");

        if (!$stmt->bind_param("i", $idUsuario))
          throw new RuntimeException("Error al asignar los parámetros");

        if (!$stmt->execute())
          throw new RuntimeException("Error al eliminar al usuario con ID '{$idUsuario}'");

        setcookie("msg[info]", 'Usuario eliminado correctamente', time() + 2);
        break;

      default:
        throw new RuntimeException("Acción de usuario desconocida: '{$accion}'");
    }

    $stmt->close();
  } catch (RuntimeException $e) {
    setcookie("msg[error]", $e->getMessage(), time() + 2);
    $GLOBALS['msg']['error'] = $e->getMessage();
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      // Manejo de error al cerrar la conexión
    }
  }
}

function renderCabecera()
{
  ?>
  <header>
    <div class="py-4 px-2 lg:mx-4 xl:mx-12 ">
      <div>
        <nav class="flex items-center justify-between flex-wrap  ">
          <div class="block lg:hidden">
            <button
              class="navbar-burger flex items-center px-3 py-2 border rounded text-white border-white hover:text-white hover:border-white">
              <svg class="fill-current h-6 w-6 text-gray-700" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <title>Menu</title>
                <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z" />
              </svg>
            </button>
          </div>
          <div id="main-nav" class="w-full flex-grow lg:flex items-center lg:w-auto hidden  ">
            <div class="text-sm lg:flex-grow mt-2 animated jackinthebox xl:mx-8">
              <a href="./index.php"
                class="block lg:inline-block text-md font-bold  text-orange-500  sm:hover:border-indigo-400  hover:text-orange-500 mx-2 focus:text-blue-500  p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg">
                INICIO
              </a>

              <?php
              if (!empty($_SESSION['control'])) {
                // Si la sesión está iniciada
                if (esAdmin($_SESSION['control'])) {
                  // Si el usuario es administrador
                  echo "<a href='./gestion.php'
                    class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                    GESTIONAR</a>";
                  /**
                   * Se muestra el enlace de registro cuando se loguee un administrador,
                   * ya que tiene la posibilidad de registrar a un nuevo usuario o un administrador
                   */
                  echo "<a href='./registrarse.php'
                    class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                    REGISTRAR</a>";
                } else {
                  // Si el usuario no es administrador
                  echo "<a href='./elaborar.php'
                    class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                    PEDIR</a>";
                }
                // Mostrar otros enlaces comunes
                echo "<a href='./mis_datos.php'
                  class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                  MIS DATOS</a>
                  <a href='./salir.php'
                  class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-red-500 mx-2 focus:text-red-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                  CERRAR SESIÓN</a>";
              } else {
                // Si no se ha iniciado sesión
                echo "<a href='./iniciar_sesion.php'
                  class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                  INICIAR SESIÓN</a>";

                // Mostrar el enlace de registro
                echo "<a href='./registrarse.php'
                  class='block lg:inline-block text-md font-bold text-gray-900 sm:hover:border-indigo-400 hover:text-orange-500 mx-2 focus:text-blue-500 p-1 hover:bg-gray-300 sm:hover:bg-transparent rounded-lg transition duration-300'>
                  REGISTRARSE</a>";
              }
              ?>
            </div>
          </div>
        </nav>
      </div>
    </div>
    <script defer>
      document.addEventListener('DOMContentLoaded', function () {
        var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
        if ($navbarBurgers.length > 0) {
          $navbarBurgers.forEach(function ($el) {
            $el.addEventListener('click', function () {
              var $target = document.getElementById('main-nav');
              $target.classList.toggle('hidden');
            });
          });
        }
      });
    </script>
  </header>
  <?php
}

function esAdmin($idUsuario)
{
  if (empty($idUsuario))
    return false;
  $conexion = conexion($_ENV['DB_DATABASE']);
  $esAdmin = false;
  try {
    if (!$conexion)
      throw new RuntimeException("Conexión fallida a la base de datos");
    $esAdmin = $conexion->query("
    SELECT `idUsuario` 
    FROM `usuario` 
    WHERE `idUsuario` = $idUsuario AND `tipoUsuario` = 'admin'")->num_rows > 0;
  } catch (RuntimeException) {
    $esAdmin = false;
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      //
    }
  }
  return $esAdmin;
}

function renderMsg($msg)
{
  if (!empty($msg['error'])) {
    $msgText = $msg['error'];
    $msgColor = "from-red-600 to-red-400";
  } else {
    $msgText = $msg['info'];
    $msgColor = "from-green-600 to-green-400";
  }
  ?>
  <section class="flex py-4 fixed bottom-0" onclick="this.remove()">
    <div
      class="font-regular relative block w-auto rounded-lg bg-gradient-to-tr <?= $msgColor ?> px-4 py-4 text-base text-white"
      data-dismissible="alert">
      <div class="absolute top-4 left-4">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
          class="h-6 w-6">
          <path fill-rule="evenodd"
            d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"
            clip-rule="evenodd"></path>
        </svg>
      </div>
      <div class="ml-8 mr-12">
        <?= $msgText ?>
      </div>
      <span style="position: absolute; top: 50%; transform: translateY(-50%); right: 15px; cursor: pointer;"
        onclick="event.stopPropagation(); this.closest('section').remove();"><svg xmlns="http://www.w3.org/2000/svg"
          height="24" viewBox="0 -960 960 960" width="24" fill="white">
          <path
            d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
        </svg></span>
    </div>
  </section>

  <?php
}

function renderMisDatos($idUsuario)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if (!$conexion)
      throw new RuntimeException("Error al conectarse a la base de datos.");
    $stmt = $conexion->stmt_init();
    if (!$stmt->prepare("SELECT `uUsuario`, `nombreReal`, `direccion`, `telefono`, `fecha_creacion` FROM `usuario` WHERE `idUsuario` = ?"))
      throw new RuntimeException();
    if (!$stmt->bind_param("i", $idUsuario))
      throw new RuntimeException();
    if (!$stmt->execute())
      throw new RuntimeException();
    $misDatos = $stmt->get_result()->fetch_assoc();
    if (!$misDatos)
      throw new RuntimeException();
    $misPizzas = "
    SELECT 
	    pizza_usuario.idPizza as `idPizza`,
      pizza.nombrePizza as `nombrePizza`,
      base.precioBase as `precioBase`,
      sum(ingrediente.precioIngrediente * ingrediente_pizza.cantidad) as `totalIngredientes`,
      pizza_usuario.cantidad as `ejemplares`,
      (sum(ingrediente.precioIngrediente * ingrediente_pizza.cantidad) + base.precioBase) * pizza_usuario.cantidad as `totalPizza`
    FROM pizza_usuario 
    INNER JOIN ingrediente_pizza ON pizza_usuario.idPizza = ingrediente_pizza.idPizza 
    INNER JOIN pizza ON pizza_usuario.idPizza = pizza.idPizza
    INNER JOIN ingrediente ON ingrediente_pizza.idIngrediente = ingrediente.idIngrediente
    INNER JOIN base ON pizza.idBase = base.idBase
    WHERE pizza_usuario.idUsuario = ?
    GROUP BY pizza_usuario.idPizza
    ";
    if (!$stmt->prepare($misPizzas))
      throw new RuntimeException();
    if (!$stmt->bind_param("i", $idUsuario))
      throw new RuntimeException();
    if (!$stmt->execute())
      throw new RuntimeException();
    $misPizzas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    ?>
    <section>
      <h2 class="text-xl font-bold mb-8">Datos personales</h2>
      <form class="w-full max-w-lg" action="./mis_datos.php" method="post">
        <div class="flex flex-col -mx-3 gap-3">
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
              Usuario
            </label>
            <input
              class="appearance-none block w-full bg-gray-600 text-white border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="text" placeholder="Usuario" disabled value="<?= $misDatos['uUsuario'] ?>">
          </div>
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombreReal">
              Nombre completo
            </label>
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="text" placeholder="Nombre" name="nombreReal" value="<?= $misDatos['nombreReal'] ?>">
          </div>
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombreReal">
              Contraseña
            </label>
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="password" placeholder="************" name="passwd">
          </div>
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="direccion">
              Dirección
            </label>
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="text" placeholder="Avenida 123 #123" name="direccion" value="<?= $misDatos['direccion'] ?>">
          </div>
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="telefono">
              Teléfono
            </label>
            <input
              class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="tel" placeholder="123123123" name="telefono" value="<?= $misDatos['telefono'] ?>">
          </div>
          <div class="w-full px-3 max-w-96">
            <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="fecha_creacion">
              Fecha de creación
            </label>
            <input
              class="appearance-none block w-full bg-gray-600 text-white border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
              type="datetime" disabled value="<?= $misDatos['fecha_creacion'] ?>">
          </div>
        </div>
        <button
          class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-orange-500/20 transition-all hover:shadow-lg hover:shadow-orange-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          type="submit" data-ripple-light="true">
          Actualizar
        </button>
        <button
          class="middle none mt-6 center rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
          data-ripple-light="true" type="button" onclick="window.location='./mis_datos.php?delete=true'">
          Eliminar mi cuenta
        </button>
      </form>
    </section>

    <section>
      <h2 class="text-xl font-bold mb-8">Pizzas solicitadas</h2>
      <?php
      if (!empty($misPizzas)) {
        ?>
        <table class="border-collapse w-max">
          <thead>
            <tr>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>
                Identificador</th>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Nombre
                de la pizza</th>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Precio
                de la base</th>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Precio
                total ingredientes</th>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Nº de
                ejemplares</th>
              <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Total
                pizza</th>
            </tr>
          </thead>
          <tbody>
            <?php
            foreach ($misPizzas as $value) {
              ?>
              <tr
                class='bg-white lg:hover:bg-gray-100 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['idPizza'] ?>
                </td>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['nombrePizza'] ?>
                </td>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['precioBase'] ?>
                </td>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['totalIngredientes'] ?>
                </td>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['ejemplares'] ?>
                </td>
                <td
                  class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                  <?= $value['totalPizza'] ?>
                </td>
              </tr>
              <?php
            }
            ?>
          </tbody>
        </table>
        <?php
      } else {
        echo "<p>Aún no has encargado pizzas</p>";
      }
      ?>
    </section>
    <?php
  } catch (\Throwable $e) {
    $GLOBALS['msg']['error'] = !empty($e->getMessage()) ? $e->getMessage() : "No se han podido obtener los datos del usuario";
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      // error al cerrar la conexión
    }
  }
}

function listarEnum($enumResult, $name, $selected = [], $isMulti = false, $isOptional = false)
{
  $lista = [];
  $enum = preg_replace("/^enum|^set|'|\(|\)/", "", $enumResult['Type']);
  $enumValues = explode(",", $enum);
  /* BOOTSTRAP CLASS */
  $class = "block appearance-none w-full bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500";
  array_push($lista, "<select class='$class' name='$name" . ($isMulti ? "[]' multiple" : "'") . ">");
  if ($isOptional)
    array_push($lista, "<option value=''>Todos</option><hr>");

  foreach ($enumValues as $value) {
    $isSelected = ($isMulti && in_array($value, (array) $selected)) || (!$isMulti && $value == $selected);
    array_push($lista, "<option value='$value' " . ($isSelected ? 'selected' : '') . ">$value</option>");
  }
  array_push($lista, "</select>");
  return $lista;
}

function renderFormularioLogin($datos = null)
{
  ?>
  <form class="w-full max-w-lg" action="./iniciar_sesion.php" method="post">
    <div class="flex flex-col -mx-3 gap-3">
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="uUsuario">
          Usuario
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="Usuario" name="uUsuario" value="<?= $datos['uUsuario'] ?? '' ?>">
      </div>
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="passwd">
          Contraseña
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="password" placeholder="***********" name="passwd" value="">
      </div>
    </div>
    <button
      class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-orange-500/20 transition-all hover:shadow-lg hover:shadow-orange-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      data-ripple-light="true">
      Iniciar sesión
    </button>
  </form>
  <?php
}

function renderFormularioRegistro($datos = null)
{
  ?>
  <form class="w-full max-w-lg" action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
    <div class="flex flex-wrap -mx-3 mb-4">
      <div class="w-full md:w-1/2 px-3 mb-4">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombreReal">
          Nombre completo
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="Usuario Apellido" name="nombreReal" value="<?= $datos['nombreReal'] ?? '' ?>">
      </div>
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="uUsuario">
          Nombre de usuario
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="usuario" name="uUsuario" value="<?= $datos['uUsuario'] ?? '' ?>">
      </div>
    </div>
    <div class="flex flex-wrap -mx-3 mb-4">
      <div class="w-full px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="passwd">
          Contraseña
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="password" placeholder="******************" name="passwd" value="">
      </div>
      <div class="w-full px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="direccion">
          Dirección postal
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 mb-3 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="Dirección 123 #123" name="direccion" value="<?= $datos['direccion'] ?? '' ?>">
      </div>
    </div>
    <div class="flex flex-wrap -mx-3 mb-2">
      <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="telefono">
          Teléfono
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="123123123" name="telefono" value="<?= $datos['telefono'] ?? '' ?>">
      </div>
      <?php
      if (esAdmin($_SESSION['control'] ?? null)) {
        ?>
        <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
          <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="grid-state">
            Tipo de usuario
          </label>
          <div class="relative">
            <?php
            $conexion = conexion($_ENV['DB_DATABASE']);
            $enumTipoUsuario = implode(
              "",
              listarEnum(
                $conexion->query(
                  "DESCRIBE `usuario` `tipoUsuario`"
                )->fetch_assoc(),
                'tipoUsuario',
                $datos['tipoUsuario'] ?? ''
              )
            );
            $conexion->close();
            echo "$enumTipoUsuario";
            ?>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
              <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
              </svg>
            </div>
          </div>
        </div>
        <?php
      }
      ?>
    </div>
    <button
      class="middle none mt-6 center rounded-lg bg-orange-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-pink-500/20 transition-all hover:shadow-lg hover:shadow-pink-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      data-ripple-light="true">
      Registrarse
    </button>
  </form>
  <?php
}

function renderFormularioPizza()
{
  echo "<h2 class='text-xl font-bold mb-2' >Agregar pizza</h2>";
  echo "<p class='mb-4'><span class='font-bold'>Nombre del encargo: </span>";
  echo $_SESSION['pedido']['nombrePedido'] ?? '(sin nombre)';
  echo "</p>";
  $conexion = conexion($_ENV['DB_DATABASE']);
  $basePizza = null;
  $ingredientes = null;
  try {
    if (!$conexion)
      throw new RuntimeException('No se ha podido establecer conexión');
    $basePizza = $conexion->query("SELECT `idBase`, `nombreBase`, `precioBase` FROM `base`");
    if (!$basePizza)
      throw new RuntimeException('Error al obtener las bases de la pizza');
    $basePizza = $basePizza->fetch_all(MYSQLI_ASSOC);
    $ingredientes = $conexion->query("
      SELECT `idIngrediente`, `nombreIngrediente`, `precioIngrediente`
      FROM `ingrediente`");
    if (!$ingredientes)
      throw new RuntimeException("Error al obtener los ingredientes");
    $ingredientes = $ingredientes->fetch_all(MYSQLI_ASSOC);
  } catch (RuntimeException $e) {
    $GLOBALS['msg']['error'] = $e->getMessage();
  } finally {
    try {
      if ($conexion !== null) {
        if (!$conexion->close())
          throw new RuntimeException();
      }
    } catch (RuntimeException) {
      //
    }
  }
  ?>
  <form action="./elaborar.php?pos=1" method="post">
    <div class="md:grid md:grid-cols-4 w-3/4 gap-3 flex flex-col">
      <div class="w-max">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombrePizza">
          Nombre pizza
        </label>
        <input
          class="appearance-none bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="(opcional)" name="nombrePizza" value="">
      </div>
      <div class="w-max">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="cantidad">
          Número de ejemplares
        </label>
        <input
          class="appearance-none bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="1" name="cantidad" value="1">
      </div>
      <div class="w-max">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="idBase">
          Base de la pizza
        </label>
        <div class="relative w-max">
          <select
            class="appearance-none bg-gray-200 border border-gray-200 text-gray-700 py-3 px-4 pr-8 rounded leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
            name="idBase">
            <?php
            if (!empty($basePizza)) {
              foreach ($basePizza as $value) {
                echo "<option value='{$value['idBase']}'>{$value['nombreBase']} (€{$value['precioBase']})</option>";
              }
            }
            ?>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
              <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
            </svg>
          </div>
        </div>
      </div>
      <?php
      if (!empty($ingredientes)) {
        foreach ($ingredientes as $value) {
          echo "
        <div class='w-max'>
          <label class='block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2' for='ingredientes[{$value['idIngrediente']}]'>
            {$value['nombreIngrediente']} (€{$value['precioIngrediente']}/ud): 
          </label>
          <input class='appearance-none bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500' type='number' name='ingredientes[{$value['idIngrediente']}]'>
        </div>";
        }
      }
      ?>
    </div>
    <button
      class="middle none mt-6 center rounded-lg bg-gray-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-gray-500/20 transition-all hover:shadow-lg hover:shadow-gray-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      data-ripple-light="true" type="button" onclick="location='./elaborar.php?pos=1'">Anterior</button>
    <button
      class="middle none mt-6 center rounded-lg bg-yellow-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-yellow-500/20 transition-all hover:shadow-lg hover:shadow-yellow-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      type="submit">Agregar</button>
    <button
      class="middle none mt-6 center rounded-lg bg-red-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-red-500/20 transition-all hover:shadow-lg hover:shadow-red-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      data-ripple-light="true" type="button" onclick="location='./elaborar.php?cancelar=true'">Cancelar encargo</button>
  </form>
  <?php
}

function obtenerPrecioTotalPizza($pizza)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  $precioTotal = 0;
  try {
    if (!$conexion)
      throw new RuntimeException();
    $precioIngredientes = $conexion->query("
    SELECT `idIngrediente`, `precioIngrediente` 
    FROM `ingrediente`")->fetch_all(MYSQLI_ASSOC);

    $precioBase = $conexion->query("
    SELECT `precioBase` 
    FROM `base` 
    WHERE `idBase` = {$pizza['idBase']}")->fetch_assoc()['precioBase'];

    $precioTotal = floatval($precioBase);
    if (!empty($pizza['ingredientes'])) {
      foreach ($pizza['ingredientes'] as $idIngrediente => $cantidad) {
        if (!empty($cantidad)) {
          $precioIngrediente = array_reduce($precioIngredientes, function ($prev, $curr) use ($idIngrediente) {
            if ($curr['idIngrediente'] == $idIngrediente) {
              return $curr['precioIngrediente'];
            }
            return $prev;
          }, 0);
          $precioTotal += floatval($precioIngrediente) * intval($cantidad);
        }
      }
    }
  } catch (RuntimeException) {

  } finally {
    try {
      if ($conexion !== null)
        $conexion->close();
    } catch (RuntimeException) {
      //
    }
  }
  return $precioTotal;
}
function renderPedidoPizza($pizza)
{
  $nombrePizza = $pizza['nombrePizza'] ?? '(sin nombre)';
  $precioTotalPizza = obtenerPrecioTotalPizza($pizza);
  return [
    'msg' => [
      'nombrePizza' => $nombrePizza,
      'precioTotalPizza' => $precioTotalPizza,
    ],
    'precioTotal' => $precioTotalPizza
  ];
}

function renderIngredientes($subaccion)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if ($conexion === null)
      throw new RuntimeException("No se ha podido establecer conexión con la base de datos");
    $ingredientes = $conexion->query("SELECT * FROM `ingrediente`");
    if (!$ingredientes)
      throw new RuntimeException("No se pudieron obtener los ingredientes");
    $ingredientes = $ingredientes->fetch_all(MYSQLI_ASSOC);
    $nombreAcciones = [
      '1' => "Listar",
      '3' => "Actualizar",
      '4' => "Eliminar",
    ];
    ?>
    <h2 class="text-xl font-bold mb-3">
      <?= $nombreAcciones[$subaccion] ?> ingrediente
    </h2>
    <table class="border-collapse w-max">
      <thead>
        <tr>
          <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>
            IdIngrediente</th>
          <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Nombre
          </th>
          <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Precio
            &#40;&euro;&#41;</th>
          <?php
          if ($subaccion == '3' || $subaccion == '4') {
            ?>
            <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>Acción
            </th>
            <?php
          }
          ?>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($ingredientes as $value) {
          ?>
          <tr
            class='bg-white lg:hover:bg-gray-100 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
            <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
              <?= $value['idIngrediente'] ?>
            </td>
            <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
              <?= $value['nombreIngrediente'] ?>
            </td>
            <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
              <?= $value['precioIngrediente'] ?>
            </td>
            <?php
            if ($subaccion == '3' || $subaccion == '4') {
              ?>
              <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
                <a class="<?= $subaccion == '3' ? 'text-orange-500' : 'text-red-500' ?>"
                  href="./gestion.php?accion=1&subaccion=<?= "$subaccion&idIngrediente={$value['idIngrediente']}" ?>">
                  <?= $nombreAcciones[$subaccion] ?>
                </a>
              </td>
              <?php
            }
            ?>
          </tr>
          <?php
        }
        ?>
      </tbody>

    </table>
    <?php
  } catch (RuntimeException $e) {
    $GLOBALS['msg']['error'] = $e->getMessage();
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      //throw $th;
    }
  }
}

function renderFormularioIngrediente($subaccion, $datos, $idIngrediente = null)
{
  ?>
  <h2 class="text-xl font-bold mb-3">
    <?= $subaccion == '2' ? "Añadir" : "Actualizar" ?> ingrediente
  </h2>
  <form
    action="./gestion.php?accion=1&subaccion=<?= "$subaccion" . (!empty($idIngrediente) ? "&idIngrediente=$idIngrediente" : '') ?>"
    method="post">
    <div class="flex flex-col -mx-3 gap-3">
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="nombreIngrediente">
          Nombre ingrediente
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="Ingrediente" name="nombreIngrediente" value="<?= $datos['nombreIngrediente'] ?? '' ?>">
      </div>
      <div class="w-full md:w-1/2 px-3">
        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2" for="precioIngrediente">
          Precio
        </label>
        <input
          class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
          type="text" placeholder="1.00" name="precioIngrediente" value="<?= $datos['precioIngrediente'] ?? '' ?>">
      </div>
    </div>
    <button
      class="middle none mt-6 center rounded-lg <?= $subaccion == '2' ? 'bg-blue-500 hover:shadow-blue-500/40 shadow-blue-500/20' : 'bg-orange-500 hover:shadow-orange-500/40 shadow-orange-500/20' ?> py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md transition-all hover:shadow-lg focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
      data-ripple-light="true">
      <?= $subaccion == '2' ? "Añadir" : "Actualizar" ?>
    </button>
  </form>
  <?php
}

function obtenerDatosIngrediente($idIngrediente)
{
  $ingrediente = null;
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if ($conexion === null)
      throw new RuntimeException("No se pudo establecer conexión con la base de datos");
    $stmt = $conexion->stmt_init();
    if (!$stmt)
      throw new RuntimeException("No se pudo obtener la información del ingrediente (#1)");
    if (!$stmt->prepare("SELECT `nombreIngrediente`, `precioIngrediente` FROM `ingrediente` WHERE `idIngrediente` = ?"))
      throw new RuntimeException("No se pudo obtener la información del ingrediente (#2)");
    if (!$stmt->bind_param("i", $idIngrediente))
      throw new RuntimeException("No se pudo obtener la información del ingrediente (#3)");
    if (!$stmt->execute())
      throw new RuntimeException("No se pudo obtener la información del ingrediente (#4)");
    $ingrediente = $stmt->get_result();
    if (!$ingrediente)
      throw new RuntimeException("No se pudo obtener la información del ingrediente (#5)");
    $ingrediente = $ingrediente->fetch_assoc();
    $stmt->close();
  } catch (RuntimeException $e) {
    $GLOBALS['msg']['error'] = $e->getMessage();
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      //throw $th;
    }
  }
  return $ingrediente;
}

function accionIngrediente($subaccion, $nombreIngrediente, $precioIngrediente, $idIngrediente = null)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  try {
    if (!$conexion)
      throw new RuntimeException("No se pudo conectar a la base de datos");
    $stmt = $conexion->stmt_init();
    if (!$stmt)
      throw new RuntimeException("Error durante la acción sobre el ingrediente (#1)");
    $query = null;
    $query = ($subaccion == '3')
      ? "UPDATE `ingrediente` SET `nombreIngrediente` = ?, `precioIngrediente` = ? WHERE `idIngrediente` = ?"
      : "INSERT INTO `ingrediente` VALUES (NULL, ?, ?)";
    if (!$stmt->prepare($query))
      throw new RuntimeException("Error durante la acción sobre el ingrediente (#2)");
    if ($subaccion == '3') {
      if (!$stmt->bind_param("ssi", $nombreIngrediente, $precioIngrediente, $idIngrediente))
        throw new RuntimeException("Error durante la acción sobre el ingrediente (#3)");
    } else {
      if (!$stmt->bind_param("ss", $nombreIngrediente, $precioIngrediente))
        throw new RuntimeException("Error durante la acción sobre el ingrediente (#3)");
    }
    if (!$stmt->execute())
      throw new RuntimeException("Error durante la acción sobre el ingrediente (#4)");
    $stmt->close();
    $GLOBALS['msg']['info'] = "Ingrediente " . ($subaccion == '3' ? "actualizado" : "agregado") . " correctamente";
  } catch (RuntimeException $e) {
    /* setcookie("msg[error]", $e->getMessage(), time() + 2); */
    $GLOBALS['msg']['info'] = $e->getMessage();
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (RuntimeException) {
      //throw $th;
    }
  }
}

function renderTablaGestion($datos, $subaccion, $titulo = null)
{
  $nombreSubacciones = [
    '1' => 'Ingredientes más escogidos',
    '2' => 'Bases más escogidas',
    '3' => 'Clientes con más pizzas',
  ]
    ?>
  <h2 class="text-xl font-bold mb-3">
    <?php
    if (!empty($titulo)) {
      echo $titulo;
    } else {
      echo "Top 3 {$nombreSubacciones[$subaccion]}";
    }
    ?>
  </h2>
  <table class="border-collapse w-max">
    <thead>
      <tr>
        <?php
        $keys = array_keys($datos[0]);
        foreach ($keys as $val) {
          ?>
          <th class='p-2 font-bold uppercase bg-gray-200 text-gray-600 border border-gray-300 hidden lg:table-cell'>
            <?= $val ?>
          </th>
          <?php
        }
        ?>
      </tr>
    </thead>
    <tbody>
      <?php
      foreach ($datos as $value) {
        ?>
        <tr
          class='bg-white lg:hover:bg-gray-100 flex lg:table-row flex-row lg:flex-row flex-wrap lg:flex-no-wrap mb-10 lg:mb-0'>
          <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
            <?= $value[$keys[0]] ?>
          </td>
          <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
            <?= $value[$keys[1]] ?>
          </td>
          <td class='w-full lg:w-auto p-2 text-gray-800 text-center border border-b block lg:table-cell relative lg:static'>
            <?= $value[$keys[2]] ?>
          </td>
        </tr>
        <?php
      }
      ?>
    </tbody>
  </table>
  <?php
}

function procesarPedido($pedido, $idUsuario)
{
  $conexion = conexion($_ENV['DB_DATABASE']);
  $resultado = false;
  $error = null;
  try {
    if (!$conexion)
      throw new RuntimeException("Conexión fallida con la base de datos");

    if (!$conexion->begin_transaction())
      throw new RuntimeException("Error al iniciar la transacción");

    try {
      $stmt = $conexion->stmt_init();
      if (!$stmt)
        throw new RuntimeException("Error al iniciar el objeto stmt");

      foreach ($pedido['pizzas'] as $pizza) {
        $stmt->prepare("INSERT INTO `pizza` (`idBase`, `nombrePizza`) VALUES (?, ?)");
        $stmt->bind_param("is", $pizza['idBase'], $pizza['nombrePizza']);
        $stmt->execute();
        $idPizza = $conexion->query("SELECT MAX(`idPizza`) AS `idPizza` FROM `pizza`")->fetch_assoc()['idPizza'];

        $stmt->prepare("INSERT INTO pizza_usuario (idPizza, idUsuario, cantidad) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $idPizza, $idUsuario, $pizza['cantidad']);
        $stmt->execute();

        foreach ($pizza['ingredientes'] as $idIngrediente => $cantidad) {
          if (!empty($cantidad)) {
            $stmt->prepare("INSERT INTO ingrediente_pizza (idIngrediente, idPizza, cantidad) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $idIngrediente, $idPizza, $cantidad);
            $stmt->execute();
          }
        }
      }
      $resultado = $conexion->commit();
      if (!$resultado)
        throw new RuntimeException($conexion->error);
    } catch (\Throwable $e) {
      $conexion->rollback();
      throw new RuntimeException($e);
    }
  } catch (\Throwable $e) {
    $error = "Error al procesar el encargo: {$e->getMessage()}";
  } finally {
    try {
      if ($conexion !== null)
        if (!$conexion->close())
          throw new RuntimeException();
    } catch (\Throwable $th) {
      //throw $th;
    }
  }
  return ['resp' => $resultado, 'error' => $error];
}

function renderGestion()
{
  $accion = intval($_GET['accion']);
  $subaccion = intval($_GET['subaccion']);
  switch ($accion) {
    case 1:
      switch ($subaccion) {
        case 1:
          renderIngredientes($subaccion);
          break;
        case 2:
        case 3:
          if (!empty($_POST)) {
            $nombreIngrediente = trim(filter_var($_POST['nombreIngrediente'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
            $precioIngrediente = $_POST['precioIngrediente'] ?? '';
            if (
              !preg_match("/^[\w\sáÁéÉíÍóÓúÚñÑ]{3,50}$/", $nombreIngrediente)
              || !preg_match("/^([\d]{1,7}|[\d]{1,7}\.[\d]{1,2})$/", $precioIngrediente)
            ) {
              $GLOBALS['msg']['error'] = "Datos inválidos o incompletos en los campos del formulario";
            } else {
              accionIngrediente($subaccion, $nombreIngrediente, $precioIngrediente, $_GET['idIngrediente'] ?? '');
            }
          }
          if (isset($_GET['idIngrediente']) || $subaccion == '2') {
            $datos = ($subaccion == '3') ? obtenerDatosIngrediente(intval($_GET['idIngrediente'])) : $_POST ?? [];
            renderFormularioIngrediente($subaccion, $datos, $_GET['idIngrediente'] ?? '');
          } else {
            renderIngredientes($subaccion);
          }
          break;
        case 4:
          if (isset($_GET['idIngrediente']) && is_numeric($_GET['idIngrediente'])) {
            $idIngrediente = intval(trim($_GET['idIngrediente']));
            $conexion = conexion($_ENV['DB_DATABASE']);
            try {
              if ($conexion === null)
                throw new RuntimeException("No se pudo establecer conexión con la base de datos");
              $stmt = $conexion->stmt_init();
              if (!$stmt)
                throw new RuntimeException("Error durante el borrado del ingrediente (#1)");
              if (!$stmt->prepare("DELETE FROM `ingrediente` WHERE `idIngrediente` = ?"))
                throw new RuntimeException("Error durante el borrado del ingrediente (#2)");
              if (!$stmt->bind_param("i", $idIngrediente))
                throw new RuntimeException("Error durante el borrado del ingrediente (#3)");
              if (!$stmt->execute())
                throw new RuntimeException("Error durante el borrado del ingrediente (#4)");
              $stmt->close();
              $GLOBALS['msg']['info'] = "Ingrediente (ID: $idIngrediente) eliminado correctamente";
            } catch (RuntimeException $e) {
              $GLOBALS['msg']['error'] = $e->getMessage();
            } finally {
              try {
                if ($conexion !== null)
                  if (!$conexion->close())
                    throw new RuntimeException();
              } catch (RuntimeException) {
                //throw $th;
              }
            }
          }
          renderIngredientes($subaccion);
          break;
      }
      break;
    case 2:
      switch ($subaccion) {
        case 1:
          $conexion = conexion($_ENV['DB_DATABASE']);
          try {
            if ($conexion === null)
              throw new RuntimeException("");
            $resultado = $conexion->query("
            SELECT 
              ingrediente.nombreIngrediente AS nombreIngrediente, 
              ingrediente_pizza.idIngrediente AS idIngrediente, 
              COUNT(ingrediente_pizza.idIngrediente) AS cantidad
            FROM `ingrediente_pizza` 
            INNER JOIN ingrediente 
            ON ingrediente_pizza.idIngrediente = ingrediente.idIngrediente 
            GROUP BY idIngrediente
            ORDER BY cantidad DESC
            LIMIT 3");
            if (!$resultado)
              throw new RuntimeException("");
            $resultado = $resultado->fetch_all(MYSQLI_ASSOC);
            if (count($resultado) != 0) {
              renderTablaGestion($resultado, $subaccion);
            } else {
              echo "No hay ingredientes registrados";
            }
          } catch (\Throwable $e) {
            $GLOBALS['msg']['error'] = $e->getMessage();
          } finally {
            try {
              if ($conexion !== null)
                if (!$conexion->close())
                  throw new RuntimeException();
            } catch (RuntimeException) {
              //throw $th;
            }
          }
          break;
        case 2:
          $conexion = conexion($_ENV['DB_DATABASE']);
          try {
            if ($conexion === null)
              throw new RuntimeException("");
            $resultado = $conexion->query("
            SELECT 
              base.nombreBase AS nombreBase, 
              base.idBase AS idBase, 
              COUNT(base.idBase) AS cantidad 
            FROM `pizza` 
            INNER JOIN base 
            ON base.idBase = pizza.idBase 
            GROUP BY idBase 
            ORDER BY cantidad DESC
            LIMIT 3;");
            if (!$resultado)
              throw new RuntimeException("");
            $resultado = $resultado->fetch_all(MYSQLI_ASSOC);
            if (count($resultado) != 0) {
              renderTablaGestion($resultado, $subaccion);
            } else {
              echo "No hay bases registradas";
            }
          } catch (\Throwable $e) {
            $GLOBALS['msg']['error'] = $e->getMessage();
          } finally {
            try {
              if ($conexion !== null)
                if (!$conexion->close())
                  throw new RuntimeException();
            } catch (RuntimeException) {
              //throw $th;
            }
          }
          break;
        case 3:
          $conexion = conexion($_ENV['DB_DATABASE']);
          try {
            if ($conexion === null)
              throw new RuntimeException("Error al conectarse a la base de datos");
            $resultado = $conexion->query("
            SELECT 
              usuario.uUsuario AS uUsuario, 
              pizza_usuario.idUsuario AS idUsuario, 
              SUM(pizza_usuario.cantidad) AS cantidad 
            FROM `pizza_usuario` 
            INNER JOIN usuario 
            ON pizza_usuario.idUsuario = usuario.idUsuario 
            GROUP BY idUsuario 
            ORDER BY cantidad DESC 
            LIMIT 3;
            ");
            if (!$resultado)
              throw new RuntimeException("Error al obtener los datos");
            $resultado = $resultado->fetch_all(MYSQLI_ASSOC);
            if (count($resultado) != 0) {
              renderTablaGestion($resultado, $subaccion);
            } else {
              echo "No hay clientes registrados";
            }
          } catch (\Throwable $e) {
            $GLOBALS['msg']['error'] = $e->getMessage();
          } finally {
            try {
              if ($conexion !== null)
                if (!$conexion->close())
                  throw new RuntimeException();
            } catch (RuntimeException) {
              //throw $th;
            }
          }
          break;
      }
      break;
    case 3:
      $conexion = conexion($_ENV['DB_DATABASE']);
      try {
        if ($conexion === null)
          throw new RuntimeException("Error al conectarse a la base de datos");
        $resultado = $conexion->query("
        SELECT `idUsuario`, `uUsuario`, `tipoUsuario` 
        FROM `usuario`");
        if (!$resultado)
          throw new RuntimeException("Error al obtener los datos");
        $resultado = $resultado->fetch_all(MYSQLI_ASSOC);
        if (count($resultado) != 0) {
          renderTablaGestion($resultado, $subaccion, 'Usuarios registrados');
        } else {
          echo "No hay usuarios registrados";
        }
      } catch (\Throwable $e) {
        $GLOBALS['msg']['error'] = $e->getMessage();
      } finally {
        try {
          if ($conexion !== null)
            if (!$conexion->close())
              throw new RuntimeException();
        } catch (RuntimeException) {
          //throw $th;
        }
      }
      break;
  }
}
