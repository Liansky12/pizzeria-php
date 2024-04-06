<?php
session_start();
session_destroy();
setcookie('msg[info]', 'Sesión cerrada correctamente', time() + 2);
header('Location: index.php');