# pizzeria-php

## Descripción
Aplicación web programada en PHP 8.0

Se trata de una página web donde los usuarios registrados pueden solicitar un encargo de pizzas personalizadas, eligiendo una base de pizza, un número indeterminado de ingredientes y un número de ejemplares.

Un usuario administrador podrá gestionar la pizzería de manera que podrá ver: datos estadísticos relevantes; agregar, editar y eliminar un ingrediente; ver los usuarios registrados; entre otras cosas.

## Despligue en local

Puedes probar la aplicación dando click en el enlace incluido en el repositorio o desplegarlo en local, y para ello debes crear la base de datos y configurar correctamente las variables de entorno.

Se ha incluido un fichero SQL (`pizzeria.sql`) y un script PHP (`crear_bd.php`). Debes asegurarte de configurar las variables de entorno cambiando el nombre de `.env.example` a `.env`. Es recomendable que definas el nombre de la base de datos como `pizzeria`. En caso contrario, deberás modificar el fichero `pizzeria.sql` y cambiar el nombre de la base de datos. Si prefieres no usar el fichero `crear_bd.php`, puedes copiar la estructura de tablas del fichero `pizzeria.sql` y configurar la base de datos manualmente.

## Enlace de descarga
[Pizzería PHP](https://github.com/Liansky12/pizzeria-php/releases/tag/v1.0.1)
