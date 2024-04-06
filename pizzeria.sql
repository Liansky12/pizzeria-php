-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 16-03-2024 a las 00:13:05
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `pizzeria`
--
CREATE DATABASE IF NOT EXISTS `pizzeria` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pizzeria`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `base`
--

CREATE TABLE `base` (
  `idBase` smallint(5) UNSIGNED NOT NULL,
  `nombreBase` varchar(50) NOT NULL,
  `precioBase` decimal(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `base`
--

INSERT INTO `base` (`idBase`, `nombreBase`, `precioBase`) VALUES
(1, 'Delgada', 3.50),
(2, 'Clásica', 4.00),
(3, 'Gruesa', 4.50),
(4, 'Integral', 5.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingrediente`
--

CREATE TABLE `ingrediente` (
  `idIngrediente` smallint(5) UNSIGNED NOT NULL,
  `nombreIngrediente` varchar(50) NOT NULL,
  `precioIngrediente` decimal(9,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingrediente`
--

INSERT INTO `ingrediente` (`idIngrediente`, `nombreIngrediente`, `precioIngrediente`) VALUES
(1, 'Tomate', 0.50),
(2, 'Queso mozzarella', 1.00),
(3, 'Jamón', 1.50),
(4, 'Champiñones', 0.75),
(5, 'Pepperoni', 1.25),
(6, 'Pimientos verdes', 0.75),
(7, 'Cebolla', 0.50),
(8, 'Aceitunas negras', 0.75),
(9, 'Alcachofas', 1.00),
(10, 'Espinacas', 0.75),
(11, 'Piña', 1.25),
(12, 'Anchoas', 1.50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingrediente_pizza`
--

CREATE TABLE `ingrediente_pizza` (
  `idIngrediente` smallint(5) UNSIGNED NOT NULL,
  `idPizza` smallint(5) UNSIGNED NOT NULL,
  `cantidad` tinyint(3) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingrediente_pizza`
--

INSERT INTO `ingrediente_pizza` (`idIngrediente`, `idPizza`, `cantidad`) VALUES
(1, 1, 2),
(2, 1, 2),
(3, 1, 1),
(1, 2, 1),
(2, 2, 3),
(5, 2, 4),
(1, 3, 2),
(6, 3, 4),
(1, 4, 1),
(2, 4, 2),
(11, 4, 3),
(1, 5, 3),
(2, 5, 4),
(3, 5, 4),
(1, 6, 2),
(10, 6, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pizza`
--

CREATE TABLE `pizza` (
  `idPizza` smallint(5) UNSIGNED NOT NULL,
  `idBase` smallint(5) UNSIGNED NOT NULL,
  `nombrePizza` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pizza`
--

INSERT INTO `pizza` (`idPizza`, `idBase`, `nombrePizza`) VALUES
(1, 2, 'MiPizza usuario1 #1'),
(2, 1, 'MiPizza usuario1 #2'),
(3, 3, 'MiPizza usuario2 #1'),
(4, 2, 'MiPizza usuario3 #1'),
(5, 1, 'MiPizza usuario3 #2'),
(6, 2, 'MiPizza usuario3 #3');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pizza_usuario`
--

CREATE TABLE `pizza_usuario` (
  `idPizza` smallint(5) UNSIGNED NOT NULL,
  `idUsuario` smallint(5) UNSIGNED NOT NULL,
  `cantidad` smallint(5) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pizza_usuario`
--

INSERT INTO `pizza_usuario` (`idPizza`, `idUsuario`, `cantidad`) VALUES
(1, 2, 2),
(2, 2, 1),
(3, 3, 1),
(4, 4, 3),
(5, 4, 1),
(6, 4, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idUsuario` smallint(5) UNSIGNED NOT NULL,
  `uUsuario` varchar(100) NOT NULL,
  `passwd` varchar(255) NOT NULL,
  `nombreReal` varchar(200) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(12) DEFAULT NULL,
  `tipoUsuario` enum('admin','cliente') NOT NULL DEFAULT 'cliente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idUsuario`, `uUsuario`, `passwd`, `nombreReal`, `direccion`, `telefono`, `tipoUsuario`, `fecha_creacion`) VALUES
(1, 'admin', '$2y$10$4Bn9BHafcM0gUO2OOK.0.OzqUpmzjgjDvio.xUZt.ASDsB.Q1goli', 'Admin Nombre', 'Admin Dirección', '9876543210', 'admin', DEFAULT),
(2, 'usuario1', '$2y$10$iJJgO/UIR/pMgEGuUsVKTukviF3tirZcUmPsEwLW1kZawANksTCrW', 'Nombre1 Apellido1', 'Calle 1, Ciudad 1', '111111111', 'cliente', DEFAULT),
(3, 'usuario2', '$2y$10$iJJgO/UIR/pMgEGuUsVKTukviF3tirZcUmPsEwLW1kZawANksTCrW', 'Nombre2 Apellido2', 'Calle 2, Ciudad 2', '5555555555', 'cliente', DEFAULT),
(4, 'usuario3', '$2y$10$iJJgO/UIR/pMgEGuUsVKTukviF3tirZcUmPsEwLW1kZawANksTCrW', 'Nombre3 Apellido3', 'Calle 3, Ciudad 3', '6666666666', 'cliente', DEFAULT);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `base`
--
ALTER TABLE `base`
  ADD PRIMARY KEY (`idBase`);

--
-- Indices de la tabla `ingrediente`
--
ALTER TABLE `ingrediente`
  ADD PRIMARY KEY (`idIngrediente`);

--
-- Indices de la tabla `ingrediente_pizza`
--
ALTER TABLE `ingrediente_pizza`
  ADD KEY `fk_ingrediente_pizza_ingrediente` (`idIngrediente`),
  ADD KEY `fk_ingrediente_pizza_pizza` (`idPizza`);

--
-- Indices de la tabla `pizza`
--
ALTER TABLE `pizza`
  ADD PRIMARY KEY (`idPizza`),
  ADD KEY `fk_pizza_base` (`idBase`);

--
-- Indices de la tabla `pizza_usuario`
--
ALTER TABLE `pizza_usuario`
  ADD PRIMARY KEY (`idPizza`,`idUsuario`),
  ADD KEY `fk_pizza_usuario_usuario` (`idUsuario`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idUsuario`),
  ADD UNIQUE KEY `uUsuario` (`uUsuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `base`
--
ALTER TABLE `base`
  MODIFY `idBase` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `ingrediente`
--
ALTER TABLE `ingrediente`
  MODIFY `idIngrediente` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `pizza`
--
ALTER TABLE `pizza`
  MODIFY `idPizza` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idUsuario` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `ingrediente_pizza`
--
ALTER TABLE `ingrediente_pizza`
  ADD CONSTRAINT `fk_ingrediente_pizza_ingrediente` FOREIGN KEY (`idIngrediente`) REFERENCES `ingrediente` (`idIngrediente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ingrediente_pizza_pizza` FOREIGN KEY (`idPizza`) REFERENCES `pizza` (`idPizza`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pizza`
--
ALTER TABLE `pizza`
  ADD CONSTRAINT `fk_pizza_base` FOREIGN KEY (`idBase`) REFERENCES `base` (`idBase`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pizza_usuario`
--
ALTER TABLE `pizza_usuario`
  ADD CONSTRAINT `fk_pizza_usuario_pizza` FOREIGN KEY (`idPizza`) REFERENCES `pizza` (`idPizza`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pizza_usuario_usuario` FOREIGN KEY (`idUsuario`) REFERENCES `usuario` (`idUsuario`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
