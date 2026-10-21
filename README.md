# 🎮 XioNox Store - Sistema Web de Catálogo y Pedidos

Bienvenido al repositorio de **XioNox Store**, una plataforma web completa de comercio electrónico y gestión de pedidos de videojuegos, desarrollada con **PHP Nativo**, **MySQL 8.0**, **Docker** y **Bootstrap 5**.

---

## 🚀 Stack Tecnológico

- **Entorno:** Docker & Docker Compose
- **Backend:** PHP 8.2 Nativo (Sin frameworks) con Apache
- **Base de Datos:** MySQL 8.0
- **Frontend:** Bootstrap 5.3.3 (CDN), Bootstrap Icons y estilos personalizados para estética Gamer / Neón
- **Seguridad:**
  - Conexión centralizada mediante **PDO** con **Prepared Statements** contra Inyecciones SQL.
  - Encriptación de contraseñas con `password_hash()` (algoritmo BCRYPT).
  - Verificación estricta en inicio de sesión con `password_verify()`.
  - Regeneración de ID de sesión (`session_regenerate_id(true)`) para prevenir Session Fixation.

---

## 📁 Estructura del Proyecto

```text
tienda_videojuegos/
├── Dockerfile              # Configuración de PHP 8.2 Apache con extensiones pdo y pdo_mysql
├── docker-compose.yml      # Orquestación de servicios: web (puerto 8080) y db (MySQL)
├── init.sql                # Script de creación de tablas y seeders iniciales
├── conexion.php            # Conexión centralizada PDO con manejo de errores y variables de entorno
├── index.php               # Catálogo público, búsqueda y agregar productos al carrito
├── carrito.php             # Vista del carrito, control de cantidades, cálculo y checkout transaccional
├── login.php               # Autenticación segura con verificación de hash
├── register.php            # Registro de nuevos clientes con encriptación de contraseña
├── logout.php              # Destrucción segura de sesión
├── dashboard.php           # Panel privado: Historial para Clientes y CRUD + Pedidos para Administradores
├── includes/
│   ├── header.php          # Meta tags, fuentes de Google, Bootstrap 5 CSS y Bootstrap Icons
│   ├── navbar.php          # Barra de navegación responsiva con contador dinámico de carrito y roles
│   └── footer.php          # Pie de página y scripts de Bootstrap 5 Bundle JS
├── css/
│   └── custom.css          # Estilos gaming personalizados con colores oscuros y acentos neón
└── README.md               # Documentación completa del proyecto
```

---

## 🗄️ Estructura de Base de Datos (`init.sql`)

1. **`usuarios`**:
   - `id`: INT AUTO_INCREMENT PRIMARY KEY
   - `nombre`: VARCHAR(100)
   - `email`: VARCHAR(150) UNIQUE
   - `password`: VARCHAR(255) *(Hash BCRYPT)*
   - `rol`: ENUM('admin', 'cliente')
   - `creado_en`: TIMESTAMP

2. **`productos`**:
   - `id`: INT AUTO_INCREMENT PRIMARY KEY
   - `titulo`: VARCHAR(200)
   - `descripcion`: TEXT
   - `precio`: DECIMAL(10,2)
   - `stock`: INT
   - `imagen_url`: VARCHAR(500)
   - `creado_en`: TIMESTAMP

3. **`pedidos`**:
   - `id`: INT AUTO_INCREMENT PRIMARY KEY
   - `usuario_id`: INT *(FK hacia usuarios.id)*
   - `fecha`: DATETIME
   - `total`: DECIMAL(10,2)
   - `estado`: ENUM('Pendiente', 'Completado')

4. **`pedido_detalle`**:
   - `id`: INT AUTO_INCREMENT PRIMARY KEY
   - `pedido_id`: INT *(FK hacia pedidos.id)*
   - `producto_id`: INT *(FK hacia productos.id)*
   - `cantidad`: INT
   - `precio_unitario`: DECIMAL(10,2)

---

## 🔑 Credenciales de Acceso Iniciales (Seeders)

| Rol | Correo Electrónico | Contraseña | Descripción |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@xionox.local` | `admin` | Acceso al CRUD de videojuegos y gestión de pedidos |
| **Cliente Demo** | `cliente@xionox.local` | `123456` | Acceso a compras y su historial personal |

---

## 🛠️ Instrucciones de Despliegue con Docker

### 1. Iniciar los contenedores
Ejecuta en la raíz del proyecto:
```bash
docker compose up -d --build
```

### 2. Acceder al sistema
Abre tu navegador e ingresa a:
👉 **[http://localhost:8080](http://localhost:8080)**

### 3. Detener los contenedores
Cuando desees detener el entorno:
```bash
docker compose down
```

---

## 🕹️ Funcionalidades Principales

1. **Catálogo Dinámico (`index.php`)**:
   - Listado de videojuegos con stock en tiempo real y buscador integrado.
   - Cards modernas con efecto hover, badges de disponibilidad y precios en USD.
   - Botón interactivo "Agregar al Carrito" que almacena los datos en `$_SESSION['carrito']`.

2. **Carrito de Compras y Checkout (`carrito.php`)**:
   - Visualización de items seleccionados, imágenes en miniatura y desglose de subtotales.
   - Controles para aumentar o disminuir cantidades con validación automática de inventario.
   - Opción para eliminar items individuales o vaciar el carrito por completo.
   - **Checkout Transaccional**: Si el usuario está autenticado, registra el pedido en `pedidos` y su desglose en `pedido_detalle`, descuenta el inventario de la base de datos dentro de una transacción `beginTransaction()` / `commit()` para garantizar atomicidad.

3. **Autenticación Segura (`login.php`, `register.php`, `logout.php`)**:
   - Formularios estilizados con validación de entradas y mensajes de alerta.
   - Registro de usuarios con contraseña encriptada mediante `password_hash(..., PASSWORD_DEFAULT)`.
   - Inicio de sesión con verificación de hash mediante `password_verify(...)`.

4. **Panel de Control Privado (`dashboard.php`)**:
   - **Vista Cliente:** Historial de compras con fecha, monto, estado (Pendiente / Completado) y modal interactivo para consultar los videojuegos comprados en cada orden.
   - **Vista Administrador:**
     - Tarjetas de estadísticas (Total de Pedidos, Facturación global, Títulos en Catálogo, Clientes).
     - **CRUD de Videojuegos:** Modal para agregar títulos, actualización rápida de stock, edición de precios/descripción y eliminación.
     - **Control de Pedidos:** Tabla de todas las órdenes de la tienda con selector para actualizar su estado (`Pendiente` ➔ `Completado`) y ver el desglose de productos.
