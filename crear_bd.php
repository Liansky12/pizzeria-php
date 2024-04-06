<?php
require_once './funciones.php';

$conexion = conexion();
try {
  if (!$conexion)
    throw new RuntimeException("No se pudo establecer conexión con MySQL");
  if ($conexion->select_db($_ENV['DB_DATABASE']))
    throw new RuntimeException('La base de datos ya está creada');
  if (!file_exists("./pizzeria.sql"))
    throw new RuntimeException("El fichero SQL no existe o no tiene los permisos suficientes");
  $sqlScript = file_get_contents("./pizzeria.sql");
  if (!$sqlScript)
    throw new RuntimeException("Error al leer el fichero SQL");
  if (!$conexion->multi_query($sqlScript))
    throw new RuntimeException("No se pudo crear la base de datos: {$conexion->error}");
  $GLOBALS['msg']['info'] = "Base de datos creada correctamente";
} catch (RuntimeException $e) {
  $GLOBALS['msg']['error'] = $e->getMessage();
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
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Creación Base de Datos</title>
</head>

<body>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Crear Base de Datos Pizzería</h1>
    <pre class="-ml-10">
      Usuario administrador: admin
      Contraseña por defecto: admin

      Usuarios: usuario1 usuario2 usuario3
      Contraseña por defecto: abc123.
    </pre>
    <p>
      <button
        class="middle none mt-6 center rounded-lg bg-blue-500 py-3 px-6 font-sans text-xs font-bold uppercase text-white shadow-md shadow-blue-500/20 transition-all hover:shadow-lg hover:shadow-blue-500/40 focus:opacity-[0.85] focus:shadow-none active:opacity-[0.85] active:shadow-none disabled:pointer-events-none disabled:opacity-50 disabled:shadow-none"
        data-ripple-light="true" onclick="window.location='./index.php'">
        Ir al inicio
      </button>
    </p>
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