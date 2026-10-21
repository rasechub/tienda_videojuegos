# XioNox Store - Sistema Web de Catálogo y Pedidos

Plataforma web transaccional de comercio electrónico especializada en la gestión y venta de videojuegos. Desarrollada bajo una arquitectura basada en contenedores utilizando PHP nativo, MySQL 8.0 y Bootstrap 5.

---

## Stack Tecnológico

- **Entorno de Ejecución:** Docker & Docker Compose
- **Backend:** PHP 8.2 Nativo (Servidor Apache)
- **Base de Datos:** MySQL 8.0
- **Frontend:** Bootstrap 5.3.3, Bootstrap Icons y hojas de estilo personalizadas.
- **Seguridad y Criptografía:**
  - Prevención de Inyecciones SQL mediante **PDO** y **Prepared Statements**.
  - Hasheo de credenciales con el algoritmo **BCRYPT** (`password_hash()`).
  - Validación de autenticación estricta (`password_verify()`).
  - Mitigación de ataques de fijación de sesión mediante regeneración de identificadores (`session_regenerate_id(true)`).

---

## Estructura del Proyecto

```text
tienda_videojuegos/
├── Dockerfile              # Configuración de imagen PHP 8.2 Apache con extensiones PDO
├── docker-compose.yml      # Orquestación de contenedores web (puerto 8080) y db (MySQL)
├── init.sql                # DDL para creación de esquema y DML para inserción de datos semilla
├── conexion.php            # Patrón Singleton/Script de conexión PDO con manejo de variables de entorno
├── index.php               # Controlador principal, catálogo público y motor de búsqueda
├── carrito.php             # Gestión de sesión de compras, cálculo de importes y checkout
├── login.php               # Módulo de autenticación
├── register.php            # Módulo de registro de usuarios
├── logout.php              # Cierre seguro de sesión
├── dashboard.php           # Panel de administración (CRUD) y portal de cliente (Historial)
├── includes/
│   ├── header.php          # Configuración de cabeceras, metaetiquetas y carga de dependencias CSS
│   ├── navbar.php          # Componente de navegación responsivo con control de estado de sesión
│   └── footer.php          # Pie de página y carga de scripts (Bootstrap Bundle)
├── css/
│   └── custom.css          # Estilos de interfaz gráfica
└── README.md               # Documentación técnica del proyecto
```

---

## Esquema de Base de Datos (`init.sql`)

El sistema opera sobre cuatro entidades principales relacionadas relacionalmente:

1. **`usuarios`**:
   - `id` (INT, PK, AUTO_INCREMENT)
   - `nombre` (VARCHAR 100)
   - `email` (VARCHAR 150, UNIQUE)
   - `password` (VARCHAR 255)
   - `rol` (ENUM: 'admin', 'cliente')
   - `creado_en` (TIMESTAMP)

2. **`productos`**:
   - `id` (INT, PK, AUTO_INCREMENT)
   - `titulo` (VARCHAR 200)
   - `descripcion` (TEXT)
   - `precio` (DECIMAL 10,2)
   - `stock` (INT)
   - `imagen_url` (VARCHAR 500)
   - `creado_en` (TIMESTAMP)

3. **`pedidos`**:
   - `id` (INT, PK, AUTO_INCREMENT)
   - `usuario_id` (INT, FK -> usuarios.id)
   - `fecha` (DATETIME)
   - `total` (DECIMAL 10,2)
   - `estado` (ENUM: 'Pendiente', 'Completado')

4. **`pedido_detalle`**:
   - `id` (INT, PK, AUTO_INCREMENT)
   - `pedido_id` (INT, FK -> pedidos.id)
   - `producto_id` (INT, FK -> productos.id)
   - `cantidad` (INT)
   - `precio_unitario` (DECIMAL 10,2)

---

## Credenciales de Acceso Iniciales (Seeders)

Los siguientes accesos se generan automáticamente al inicializar la base de datos para pruebas en entornos de desarrollo:

| Perfil de Usuario | Correo Electrónico | Contraseña | Nivel de Acceso |
| :--- | :--- | :--- | :--- |
| **Administrador** | `admin@xionox.local` | `admin` | Control total: CRUD del catálogo y gestión de estados de pedidos. |
| **Cliente Demo** | `cliente@xionox.local` | `123456` | Acceso estándar: Proceso de compra y visualización de historial. |

---

## Instrucciones de Despliegue

### 1. Inicialización de Contenedores
Para construir y levantar la infraestructura en segundo plano, ejecute el siguiente comando en el directorio raíz:
```bash
docker compose up -d --build
```

### 2. Acceso a la Plataforma
Una vez que los servicios estén activos, la aplicación estará disponible en:
[http://localhost:8080](http://localhost:8080)

### 3. Detención de Servicios
Para detener y suspender el entorno de contenedores de forma segura:
```bash
docker compose down
```

---

## Funcionalidades Principales

### Catálogo de Productos y Navegación (`index.php`)
- Renderizado dinámico del inventario con validación de stock en tiempo real.
- Motor de búsqueda integrado para filtrado rápido de títulos.
- Interfaz gráfica basada en tarjetas (Cards) con indicadores de disponibilidad y estructuración de precios.

### Motor de Compras y Checkout (`carrito.php`)
- Gestión temporal de artículos mediante superglobales de sesión (`$_SESSION['carrito']`).
- Controles de incremento y decremento de unidades limitados por el inventario disponible en base de datos.
- **Transacciones ACID:** El proceso de checkout emplea `beginTransaction()` y `commit()` para asegurar que la deducción de inventario, el registro del pedido general y el desglose de artículos se ejecuten como una única operación atómica.

### Gestión de Identidad y Accesos
- Procesamiento seguro de formularios con validación de datos en servidor.
- Rutinas de registro e inicio de sesión protegidas mediante algoritmos de derivación de claves estándar de la industria.

### Panel de Control Híbrido (`dashboard.php`)
- **Portal de Cliente:** Tabla histórica de transacciones con visualización de estado y modales de consulta detallada por orden.
- **Portal Administrativo:** 
  - Panel de métricas e indicadores de rendimiento (KPIs) del negocio.
  - Interfaz completa para la gestión de inventario (Creación, lectura, actualización y eliminación de productos).
  - Gestor de despachos para visualizar y transicionar el ciclo de vida de los pedidos (de `Pendiente` a `Completado`).
