-- ==============================================================
-- Script de Inicialización de Base de Datos - XioNox Store
-- ==============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `xionox_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `xionox_store`;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Productos
CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `titulo` VARCHAR(200) NOT NULL,
    `descripcion` TEXT NOT NULL,
    `precio` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `imagen_url` VARCHAR(500) NOT NULL,
    `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `fecha` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `total` DECIMAL(10,2) NOT NULL,
    `estado` ENUM('Pendiente', 'Completado') NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT `fk_pedidos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Detalle del Pedido
CREATE TABLE IF NOT EXISTS `pedido_detalle` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pedido_id` INT NOT NULL,
    `producto_id` INT NOT NULL,
    `cantidad` INT NOT NULL,
    `precio_unitario` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================
-- Datos de Inicialización (Seeders)
-- ==============================================================

-- Usuario Administrador
-- Contraseña 'admin' encriptada con bcrypt (PASSWORD_DEFAULT de PHP)
-- Hash: $2y$12$6jr2JL9BgJf/i/7aRIxLveoSrL03dfwgDKnmUCygfRoa8Kd1.UL5e
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`) VALUES
('Administrador XioNox', 'admin@xionox.local', '$2y$12$6jr2JL9BgJf/i/7aRIxLveoSrL03dfwgDKnmUCygfRoa8Kd1.UL5e', 'admin')
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Usuario Cliente Demo
-- Contraseña '123456' encriptada con bcrypt para pruebas rápidas
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`) VALUES
('Gamer Pro', 'cliente@xionox.local', '$2y$12$b5Z4YQ7pZ45gU97ZtQhD5.8J.G6g4F3wYm4FzLw7B7t2e.b78wXpS', 'cliente')
ON DUPLICATE KEY UPDATE `email` = `email`;

-- Productos de Catálogo Requeridos (Precios en Soles S/.)
INSERT INTO `productos` (`id`, `titulo`, `descripcion`, `precio`, `stock`, `imagen_url`) VALUES
(1, 'Watch Dogs 2', 'Sumérgete en la cuna de la revolución tecnológica como Marcus Holloway, un brillante joven hacker que vive en la bahía de San Francisco.', 109.90, 15, 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=800&q=80'),
(2, 'Tom Clancy''s Rainbow Six', 'Shooter táctico en primera persona multijugador de élite donde la estrategia precisa, el trabajo en equipo y las tácticas avanzadas definen la victoria.', 74.90, 20, 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?auto=format&fit=crop&w=800&q=80'),
(3, 'CS2 Prime Status Upgrade', 'Acceso exclusivo al emparejamiento Prime, drops semanales de armas, cajas conmemorativas y acceso a servidores competitivos de alto rango.', 56.00, 50, 'https://images.unsplash.com/photo-1511512578047-dfb367046420?auto=format&fit=crop&w=800&q=80'),
(4, 'FiveM Citizen Mod Bundle', 'Colección definitiva de shaders hiperrealistas, físicas de conducción optimizadas, interfaces personalizadas y mods de rendimiento para servidores de rol.', 37.50, 35, 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?auto=format&fit=crop&w=800&q=80')
ON DUPLICATE KEY UPDATE 
    `titulo` = VALUES(`titulo`),
    `descripcion` = VALUES(`descripcion`),
    `precio` = VALUES(`precio`),
    `stock` = VALUES(`stock`),
    `imagen_url` = VALUES(`imagen_url`);
