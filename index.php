<?php
/**
 * @author Christian <tianqueal@gmail.com>
 * Aplicación web programada en PHP.
 * 
 * Se ha usado Tailwind CSS para el diseño y 
 * maquetación de la web.
 * 
 * Es necesario tener conectividad a Internet para
 * cargar el CDN de Tailwind CSS.
 */
require_once './funciones.php';
session_start();
$conexion = conexion($_ENV['DB_DATABASE']);
if (!$conexion)
  $GLOBALS['msg']['error'] = "No se ha podido establecer conexión con la base de datos. <a class='text-stone-950' href='./crear_bd.php'>Crear pizzería</a>";
else
  $conexion->close();
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <title>Pizzería | Inicio</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex flex-col">
  <?php renderCabecera(); ?>
  <main class="py-4 px-2 lg:mx-4 xl:mx-12">
    <h1 class="text-3xl font-bold mb-6">Pizzería</h1>
    <div class="container mx-auto py-12">
      <h2 class="text-2xl font-bold text-center mb-8">Bienvenida/o a nuestra Pizzería</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div class="relative overflow-hidden rounded-lg bg-black group aspect-w-4 aspect-h-3">
          <img src="./imagenes/pizza1.jpg" alt="Pizza 1"
            class="w-full h-full object-cover rounded-lg shadow-md transition-transform transform hover:scale-110">
          <div
            class="absolute inset-0 flex items-center justify-center opacity-0 bg-black bg-opacity-50 group-hover:opacity-100 transition-opacity duration-300">
            <button class="bg-red-500 text-white py-2 px-4 rounded-lg"
              onclick="window.location='./elaborar.php'">Ordenar ahora</button>
          </div>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-black group aspect-w-4 aspect-h-3">
          <img src="./imagenes/pizza2.jpg" alt="Pizza 2"
            class="w-full h-full object-cover rounded-lg shadow-md transition-transform transform hover:scale-110">
          <div
            class="absolute inset-0 flex items-center justify-center opacity-0 bg-black bg-opacity-50 group-hover:opacity-100 transition-opacity duration-300">
            <button class="bg-red-500 text-white py-2 px-4 rounded-lg"
              onclick="window.location='./elaborar.php'">Ordenar ahora</button>
          </div>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-black group aspect-w-4 aspect-h-3">
          <img src="./imagenes/pizza3.jpg" alt="Pizza 3"
            class="w-full h-full object-cover rounded-lg shadow-md transition-transform transform hover:scale-110">
          <div
            class="absolute inset-0 flex items-center justify-center opacity-0 bg-black bg-opacity-50 group-hover:opacity-100 transition-opacity duration-300">
            <button class="bg-red-500 text-white py-2 px-4 rounded-lg"
              onclick="window.location='./elaborar.php'">Ordenar ahora</button>
          </div>
        </div>
        <div class="relative overflow-hidden rounded-lg bg-black group aspect-w-4 aspect-h-3">
          <img src="./imagenes/pizza4.webp" alt="Pizza 4"
            class="w-full h-full object-cover rounded-lg shadow-md transition-transform transform hover:scale-110">
          <div
            class="absolute inset-0 flex items-center justify-center opacity-0 bg-black bg-opacity-50 group-hover:opacity-100 transition-opacity duration-300">
            <button class="bg-red-500 text-white py-2 px-4 rounded-lg"
              onclick="window.location='./elaborar.php'">Ordenar ahora</button>
          </div>
        </div>
      </div>
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