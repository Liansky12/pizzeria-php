# pizzeria-php

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/6c9e2df0-c6a5-4980-9c8f-76a613092bbb">

## Descripción
Aplicación web programada en PHP 8.0 y estilos de TailwindCSS.

### Usuarios

Se trata de una página web donde los usuarios registrados pueden solicitar un encargo de pizzas personalizadas, eligiendo una base de pizza, un número indeterminado de ingredientes y un número de ejemplares.

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/e37430e7-4748-42c1-96e7-00a7162cfdca">

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/d769f8b7-ca65-49a0-9ce0-c8c9e3af41d9">

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/ac13db6a-31fc-4fc3-bd6a-a4759f7e8c33">

Es posible visualizar de manera general la información del usuario, modificar sus datos o darse de baja en el sistema.

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/e0e41162-244d-45c2-b375-297009a2b30b">

### Administrador

Un usuario administrador podrá gestionar la pizzería de manera que podrá ver: datos estadísticos relevantes; agregar, editar y eliminar un ingrediente; ver los usuarios registrados; entre otras cosas.

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/2967cb14-95ab-40a2-a1b0-9012d61838d9">

<img width="1682" alt="image" src="https://github.com/user-attachments/assets/92cc5f7b-cc35-4225-b856-d95f3dac2d11">

## Despligue en local

Puedes probar la aplicación dando click en el enlace incluido en el repositorio o desplegarlo en local, y para ello debes crear la base de datos y configurar correctamente las variables de entorno.

Se ha incluido un fichero SQL (`pizzeria.sql`) y un script PHP (`crear_bd.php`). Debes asegurarte de configurar las variables de entorno cambiando el nombre de `.env.example` a `.env`. Es recomendable que definas el nombre de la base de datos como `pizzeria`. En caso contrario, deberás modificar el fichero `pizzeria.sql` y cambiar el nombre de la base de datos. Si prefieres no usar el fichero `crear_bd.php`, puedes copiar la estructura de tablas del fichero `pizzeria.sql` y configurar la base de datos manualmente.

## Posibles inconvenientes

Se ha optado por usar una librería para asegurarse de cargar correctamente las variables de entorno; por lo que es necesario tener instalador [Composer](https://getcomposer.org) y ejecutar el siguiente comando para descargar las dependencias:

```bash
composer install
```


## Enlace de descarga
[Pizzería PHP](https://github.com/Liansky12/pizzeria-php/releases/tag/v1.0.1)
