-- ============================================
-- EASYGES — DISTRICASCOS
-- Base de Datos completa
-- ============================================

CREATE DATABASE IF NOT EXISTS easyges CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE easyges;

-- ============================================
-- TABLA: usuarios del sistema
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)  NOT NULL,
    usuario       VARCHAR(50)   NOT NULL UNIQUE,
    password      VARCHAR(255)  NOT NULL,
    rol           ENUM('admin','facturacion','inventario','compras','vendedor') NOT NULL DEFAULT 'vendedor',
    activo        TINYINT(1)    DEFAULT 1,
    ultimo_login  DATETIME      NULL,
    created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: categorías de productos
-- ============================================
CREATE TABLE IF NOT EXISTS categorias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion TEXT,
    icono       VARCHAR(50)  DEFAULT 'fa-tag',
    activo      TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: marcas
-- ============================================
CREATE TABLE IF NOT EXISTS marcas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    pais_origen VARCHAR(60),
    activo      TINYINT(1)   DEFAULT 1,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: productos
-- Cascos, chaquetas, impermeables, guantes, botas, etc.
-- ============================================
CREATE TABLE IF NOT EXISTS productos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(50)   NOT NULL UNIQUE,
    nombre           VARCHAR(200)  NOT NULL,
    descripcion      TEXT,
    categoria_id     INT           NOT NULL,
    marca_id         INT           NOT NULL,
    precio_compra    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    precio_venta     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    impuesto_pct     DECIMAL(5,2)  DEFAULT 19.00,
    iva_incluido     TINYINT(1)    DEFAULT 1,
    stock            INT           NOT NULL DEFAULT 0,
    stock_minimo     INT           DEFAULT 3,
    -- Atributos específicos moto-gear
    talla            VARCHAR(20),
    color            VARCHAR(50),
    homologacion     VARCHAR(50),      -- ECE 22.06, DOT, SNELL (cascos)
    nivel_proteccion VARCHAR(20),      -- CE Nivel 1 / CE Nivel 2
    impermeable      TINYINT(1)    DEFAULT 0,
    activo           TINYINT(1)    DEFAULT 1,
    created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (marca_id)     REFERENCES marcas(id)
);

-- ============================================
-- TABLA: tallas disponibles por producto
-- (stock individual por talla)
-- ============================================
CREATE TABLE IF NOT EXISTS producto_tallas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT         NOT NULL,
    talla       VARCHAR(20) NOT NULL,
    stock       INT         NOT NULL DEFAULT 0,
    UNIQUE KEY uk_prod_talla (producto_id, talla),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- ============================================
-- TABLA: clientes (moteros)
-- ============================================
CREATE TABLE IF NOT EXISTS clientes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    apellido        VARCHAR(100),
    tipo_doc        ENUM('CC','NIT','CE','Pasaporte') DEFAULT 'CC',
    numero_doc      VARCHAR(20)  UNIQUE,
    email           VARCHAR(120),
    telefono        VARCHAR(20),
    direccion       TEXT,
    ciudad          VARCHAR(80),
    departamento    VARCHAR(80),
    tipo_moto       VARCHAR(100),
    estilo_moto     ENUM('Sport','Naked','Adventure','Cruiser','Enduro','Scooter','Otro'),
    talla_casco     VARCHAR(10),
    talla_ropa      VARCHAR(10),
    activo          TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: datos de la empresa (facturación)
-- ============================================
CREATE TABLE IF NOT EXISTS empresa (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nit             VARCHAR(30)   NOT NULL DEFAULT '',
    nombre          VARCHAR(200)  NOT NULL DEFAULT '',
    nombre_comercial VARCHAR(200) DEFAULT '',
    direccion       TEXT,
    ciudad          VARCHAR(80),
    departamento    VARCHAR(80),
    telefono        VARCHAR(25),
    email           VARCHAR(120),
    regimen         VARCHAR(30)   DEFAULT 'comun',
    prefijo_factura VARCHAR(10)   DEFAULT 'FEP',
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO empresa (nit, nombre, nombre_comercial, regimen, prefijo_factura) VALUES
('', 'Mi Empresa', '', 'comun', 'FEP');

-- ============================================
-- TABLA: consecutivos para facturación
-- ============================================
CREATE TABLE IF NOT EXISTS consecutivos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    prefijo         VARCHAR(10)  NOT NULL,
    ultimo_numero   INT          NOT NULL DEFAULT 0,
    anio            INT          NOT NULL,
    UNIQUE KEY uk_prefijo_anio (prefijo, anio)
);

-- ============================================
-- TABLA: proveedores
-- ============================================
CREATE TABLE IF NOT EXISTS proveedores (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(150) NOT NULL,
    nit                 VARCHAR(30)  UNIQUE,
    contacto            VARCHAR(100),
    email               VARCHAR(120),
    telefono            VARCHAR(25),
    direccion           TEXT,
    ciudad              VARCHAR(80),
    pais                VARCHAR(60)  DEFAULT 'Colombia',
    categoria_principal VARCHAR(80),
    activo              TINYINT(1)   DEFAULT 1,
    created_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLA: facturas de venta
-- ============================================
CREATE TABLE IF NOT EXISTS facturas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    numero       VARCHAR(20)   NOT NULL UNIQUE,
    cliente_id   INT           NOT NULL,
    usuario_id   INT           NOT NULL,
    fecha        DATE          NOT NULL,
    subtotal     DECIMAL(12,2) DEFAULT 0.00,
    descuento    DECIMAL(12,2) DEFAULT 0.00,
    impuesto     DECIMAL(12,2) DEFAULT 0.00,
    total        DECIMAL(12,2) DEFAULT 0.00,
    metodo_pago  ENUM('Efectivo','Tarjeta','Transferencia','Nequi','Daviplata','Credito') DEFAULT 'Efectivo',
    estado       ENUM('pendiente','pagada','anulada') DEFAULT 'pendiente',
    notas        TEXT,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- ============================================
-- TABLA: detalle de factura
-- ============================================
CREATE TABLE IF NOT EXISTS factura_detalle (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    factura_id      INT           NOT NULL,
    producto_id     INT           NOT NULL,
    talla           VARCHAR(20),
    color           VARCHAR(50),
    cantidad        INT           NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL,
    descuento_pct   DECIMAL(5,2)  DEFAULT 0.00,
    subtotal        DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (factura_id)  REFERENCES facturas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ============================================
-- TABLA: ordenes de compra a proveedores
-- ============================================
CREATE TABLE IF NOT EXISTS compras (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    numero         VARCHAR(20)   NOT NULL UNIQUE,
    proveedor_id   INT           NOT NULL,
    usuario_id     INT           NOT NULL,
    fecha          DATE          NOT NULL,
    fecha_entrega  DATE,
    subtotal       DECIMAL(12,2) DEFAULT 0.00,
    impuesto       DECIMAL(12,2) DEFAULT 0.00,
    total          DECIMAL(12,2) DEFAULT 0.00,
    estado         ENUM('pendiente','recibida','parcial','anulada') DEFAULT 'pendiente',
    notas          TEXT,
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
    FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)
);

-- ============================================
-- TABLA: detalle de compra
-- ============================================
CREATE TABLE IF NOT EXISTS compra_detalle (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    compra_id         INT           NOT NULL,
    producto_id       INT           NOT NULL,
    talla             VARCHAR(20),
    cantidad          INT           NOT NULL DEFAULT 1,
    cantidad_recibida INT           DEFAULT 0,
    precio_unitario   DECIMAL(12,2) NOT NULL,
    subtotal          DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (compra_id)   REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- ============================================
-- TABLA: movimientos de inventario
-- ============================================
CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT         NOT NULL,
    talla           VARCHAR(20),
    tipo            ENUM('entrada','salida','ajuste','devolucion') NOT NULL,
    cantidad        INT         NOT NULL,
    stock_anterior  INT         NOT NULL,
    stock_nuevo     INT         NOT NULL,
    referencia_tipo VARCHAR(30),
    referencia_id   INT,
    notas           TEXT,
    usuario_id      INT         NOT NULL,
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)
);

-- ============================================
-- TABLA: movimientos de caja
-- ============================================
CREATE TABLE IF NOT EXISTS movimientos_caja (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tipo            ENUM('ingreso','egreso') NOT NULL,
    concepto        VARCHAR(250) NOT NULL,
    monto           DECIMAL(12,2) NOT NULL,
    referencia_tipo VARCHAR(30),
    referencia_id   INT,
    fecha           DATE         NOT NULL,
    usuario_id      INT          NOT NULL,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);


-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Usuarios
INSERT INTO usuarios (nombre, usuario, password, rol) VALUES
('Administrador',  'admin',       'admin123',  'admin'),
('Carlos Rueda',   'facturacion', 'fact123',   'facturacion'),
('Laura Ospina',   'inventario',  'inv123',    'inventario'),
('Felipe Mora',    'compras',     'comp123',   'compras'),
('Valentina Cruz', 'vendedor',    'vend123',   'vendedor');

-- Categorias
INSERT INTO categorias (nombre, descripcion, icono) VALUES
('Cascos',       'Cascos integrales, modulares, jet y off-road',             'fa-hard-hat'),
('Chaquetas',    'Chaquetas de cuero, textil y softshell para moto',         'fa-vest'),
('Pantalones',   'Pantalones de moto con protecciones CE',                   'fa-person'),
('Guantes',      'Guantes de verano, invierno e impermeables',               'fa-hand'),
('Botas',        'Botas de moto, touring y off-road',                        'fa-boot'),
('Impermeables', 'Trajes de lluvia, sobrebotas y cubrecasco',                'fa-cloud-showers-heavy'),
('Equipamiento', 'Chalecos airbag, protectores de espalda, rodilleras',      'fa-shield'),
('Accesorios',   'Intercomunicadores, camaras, candados, maletas',           'fa-bag-shopping'),
('Lubricantes',  'Aceites de cadena, liquidos de freno, lubricantes',        'fa-oil-can'),
('Ropa Casual',  'Ropa casual con estetica motera sin protecciones CE',      'fa-shirt');

-- Marcas
INSERT INTO marcas (nombre, pais_origen) VALUES
('Shoei',        'Japon'),
('AGV',          'Italia'),
('Arai',         'Japon'),
('HJC',          'Corea del Sur'),
('LS2',          'Espana'),
('Alpinestars',  'Italia'),
('Dainese',      'Italia'),
('Revit',        'Paises Bajos'),
('Held',         'Alemania'),
('Klim',         'Estados Unidos'),
('TCX',          'Italia'),
('Sidi',         'Italia'),
('Oxford',       'Reino Unido'),
('Givi',         'Italia'),
('Sena',         'Estados Unidos'),
('Generico',     'Colombia');

-- Productos
INSERT INTO productos (codigo, nombre, descripcion, categoria_id, marca_id, precio_compra, precio_venta, stock, stock_minimo, talla, color, homologacion, nivel_proteccion, impermeable) VALUES
-- CASCOS
('CASCO-001', 'Shoei X-SPR Pro',         'Casco integral racing aerodinamico',          1,  1, 1200000, 2100000,  5, 1, 'M',  'Blanco',        'ECE 22.06', NULL,          0),
('CASCO-002', 'AGV K6 S',                'Casco integral sport ventilacion excelente',  1,  2,  800000, 1350000,  8, 2, 'L',  'Negro',         'ECE 22.06', NULL,          0),
('CASCO-003', 'HJC RPHA 1',              'Casco integral fibra de carbono',             1,  4,  650000, 1100000,  6, 2, 'S',  'Rojo',          'ECE 22.06', NULL,          0),
('CASCO-004', 'LS2 FF906 Advant',        'Casco modular con pantalla solar interna',    1,  5,  380000,  650000, 10, 3, 'XL', 'Gris',          'ECE 22.06', NULL,          0),
('CASCO-005', 'Arai Tour-X5',            'Casco adventure doble visera',                1,  3, 1100000, 1800000,  4, 1, 'M',  'Negro',         'ECE 22.06', NULL,          0),
-- CHAQUETAS
('CHAQ-001', 'Alpinestars Andes V3',     'Chaqueta textil touring 3en1 impermeable',    2,  6,  550000,  920000,  7, 2, 'L',  'Negro',         NULL,        'CE Nivel 2',  1),
('CHAQ-002', 'Dainese Smart Jacket',     'Chaleco airbag compatible chaqueta',          2,  7,  900000, 1500000,  4, 1, 'M',  'Negro',         NULL,        'CE Nivel 2',  0),
('CHAQ-003', 'Revit Sand 5',             'Chaqueta adventure todo terreno',             2,  8,  680000, 1150000,  5, 2, 'XL', 'Beige',         NULL,        'CE Nivel 1',  1),
('CHAQ-004', 'Held Atacama 4',           'Chaqueta touring premium cuero textil',       2,  9,  750000, 1280000,  3, 1, 'L',  'Negro',         NULL,        'CE Nivel 2',  1),
('CHAQ-005', 'Klim Latitude',            'Chaqueta ADV premium Gore-Tex',               2, 10,  980000, 1700000,  3, 1, 'M',  'Gris',          NULL,        'CE Nivel 2',  1),
-- PANTALONES
('PANT-001', 'Alpinestars Andes V3 Pant','Pantalon textil touring impermeable',         3,  6,  340000,  580000,  6, 2, '34', 'Negro',         NULL,        'CE Nivel 2',  1),
('PANT-002', 'Dainese Tempest 3 Pant',   'Pantalon lluvia sobre pantalon',              3,  7,  280000,  480000,  8, 3, 'L',  'Negro',         NULL,        'CE Nivel 1',  1),
('PANT-003', 'Revit Sand 5 Pant',        'Pantalon adventure todo terreno',             3,  8,  420000,  720000,  5, 2, 'XL', 'Beige',         NULL,        'CE Nivel 2',  1),
-- GUANTES
('GUAN-001', 'Alpinestars SP-8 v3',      'Guantes sport corto verano',                  4,  6,   95000,  160000, 15, 4, 'L',  'Negro',         NULL,        'CE Nivel 2',  0),
('GUAN-002', 'Dainese Tempest 2',        'Guantes invierno impermeables',               4,  7,  120000,  200000, 10, 3, 'M',  'Negro',         NULL,        'CE Nivel 1',  1),
('GUAN-003', 'Held Phantom II',          'Guantes cortos verano cuero',                 4,  9,  130000,  220000,  8, 3, 'XL', 'Marron',        NULL,        'CE Nivel 2',  0),
('GUAN-004', 'Revit Mosca 2',            'Guantes sport ventilados',                    4,  8,   85000,  145000, 12, 4, 'S',  'Negro Rojo',    NULL,        'CE Nivel 1',  0),
-- BOTAS
('BOTA-001', 'Alpinestars Corozal 3 DS', 'Bota touring adventure waterproof',           5,  6,  380000,  650000,  6, 2, '43', 'Negro',         NULL,        'CE Nivel 2',  1),
('BOTA-002', 'Sidi Adventure 2 Gore',    'Bota adventure Gore-Tex',                     5, 12,  520000,  890000,  4, 1, '44', 'Negro',         NULL,        'CE Nivel 2',  1),
('BOTA-003', 'TCX Blend WP',             'Bota urban impermeable estilo casual',        5, 11,  280000,  480000,  8, 2, '42', 'Marron',        NULL,        'CE Nivel 1',  1),
-- IMPERMEABLES
('IMPL-001', 'Oxford Rainseal',          'Traje de lluvia 2 piezas reflectante',        6, 13,   75000,  130000, 20, 5, 'L',  'Negro Amarillo',NULL,        NULL,          1),
('IMPL-002', 'Oxford Rainseal Pro',      'Traje de lluvia profesional',                 6, 13,  110000,  185000, 15, 4, 'XL', 'Negro',         NULL,        NULL,          1),
('IMPL-003', 'Cubrecasco impermeable',   'Cubrecasco universal reflectante',            6, 16,   18000,   32000, 30, 8, 'Unico','Amarillo',    NULL,        NULL,          1),
-- EQUIPAMIENTO
('EQUI-001', 'Dainese Pro-Armor Back S', 'Protector de espalda nivel 2',               7,  7,  145000,  245000,  8, 2, 'S',   'Negro',        NULL,        'CE Nivel 2',  0),
('EQUI-002', 'Alpinestars Bionic Plus',  'Chaleco protector integral',                  7,  6,  220000,  370000,  5, 1, 'L',   'Negro',        NULL,        'CE Nivel 2',  0),
('EQUI-003', 'Oxford Air-Vest',          'Chaleco airbag electronico autonomo',         7, 13,  980000, 1650000,  3, 1, 'M',   'Negro',        NULL,        'CE Nivel 2',  0),
('EQUI-004', 'Rodilleras Fox Launch',    'Rodilleras enduro articuladas',               7, 16,   85000,  145000, 10, 3, 'Unico','Negro',       NULL,        'CE Nivel 2',  0),
-- ACCESORIOS
('ACCE-001', 'Sena 50S',                 'Intercomunicador Bluetooth Mesh 2.0',         8, 15,  480000,  820000,  5, 1, NULL,  'Negro',        NULL,        NULL,          0),
('ACCE-002', 'Givi E340N Monolock',      'Maleta trasera 34L monokey',                  8, 14,  220000,  380000,  7, 2, NULL,  'Negro',        NULL,        NULL,          0),
('ACCE-003', 'Oxford Boss Alarm',        'Candado disco con alarma 120dB',              8, 13,   95000,  160000, 12, 3, NULL,  'Naranja',      NULL,        NULL,          0),
('ACCE-004', 'Sena 30K',                 'Intercomunicador HD Mesh',                    8, 15,  320000,  550000,  4, 1, NULL,  'Negro',        NULL,        NULL,          0),
-- LUBRICANTES
('LUBR-001', 'Motul Chain Lube Road',    'Lubricante cadena carretera 400ml',           9, 16,   18000,   32000, 30, 8, NULL,  NULL,           NULL,        NULL,          0),
('LUBR-002', 'Motul Chain Clean',        'Limpiador de cadena 400ml',                   9, 16,   16000,   28000, 25, 6, NULL,  NULL,           NULL,        NULL,          0),
('LUBR-003', 'Liqui Moly Brake Fluid',   'Liquido de frenos DOT4 500ml',                9, 16,   22000,   38000, 20, 5, NULL,  NULL,           NULL,        NULL,          0);

-- Tallas por producto (cascos ejemplo)
INSERT INTO producto_tallas (producto_id, talla, stock) VALUES
(1, 'S', 2), (1, 'M', 2), (1, 'L', 1),
(2, 'M', 3), (2, 'L', 3), (2, 'XL', 2),
(3, 'S', 2), (3, 'M', 2), (3, 'L', 2),
(4, 'M', 3), (4, 'L', 4), (4, 'XL', 3),
(5, 'S', 1), (5, 'M', 2), (5, 'L', 1);

-- Proveedores
INSERT INTO proveedores (nombre, nit, contacto, email, telefono, ciudad, pais, categoria_principal) VALUES
('Moto Gear Colombia SAS',   '900111222-3', 'Andres Pena',   'andres@motogear.co',   '3201112233', 'Bogota',   'Colombia', 'Cascos'),
('Importadora MotoSport',    '800333444-5', 'Clara Rios',    'info@motosport.co',    '3156667788', 'Medellin', 'Colombia', 'Chaquetas'),
('Adventure Riders Supply',  '901555666-7', 'Juan Valdes',   'jv@ars.co',            '3012345678', 'Cali',     'Colombia', 'Equipamiento'),
('Alpinestars Oficial Col.', '802777888-9', 'Monica Torres', 'mtorres@alpstars.co',  '3109876543', 'Bogota',   'Colombia', 'Alpinestars'),
('MotoAcces Import',         '900999000-1', 'Diego Lara',    'dlara@motoacces.co',   '3005554433', 'Bogota',   'Colombia', 'Accesorios');

-- Clientes moteros de ejemplo
INSERT INTO clientes (nombre, apellido, tipo_doc, numero_doc, email, telefono, ciudad, tipo_moto, estilo_moto, talla_casco, talla_ropa) VALUES
('Sebastian', 'Gomez',   'CC', '1020304050', 'sgomez@gmail.com',     '3001234567', 'Bogota',      'Honda CB500F',      'Naked',     'M', 'L'),
('Natalia',   'Herrera',  'CC', '1031415161', 'nherrera@hotmail.com', '3112345678', 'Medellin',   'BMW R1250GS',       'Adventure', 'S', 'M'),
('Ricardo',   'Montoya',  'CC', '1072839405', 'rmontoya@gmail.com',   '3205557890', 'Cali',       'Kawasaki Z900',     'Naked',     'L', 'XL'),
('Paula',     'Jimenez',  'CC', '1054637281', 'pjimenez@yahoo.com',   '3009998877', 'Bucaramanga','Yamaha MT-07',      'Naked',     'S', 'S'),
('Camilo',    'Torres',   'NIT','900123456-0','ventas@motoclub.co',   '3151114455', 'Bogota',     'Multiples - Club',  'Adventure', 'L', 'L');

-- ============================================
-- TABLA: metodos de pago (admin configurable)
-- ============================================
CREATE TABLE IF NOT EXISTS metodos_pago (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL,
    tipo        ENUM('efectivo','tarjeta','transferencia','credito') NOT NULL,
    activo      TINYINT(1) DEFAULT 1,
    orden       INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Metodos por defecto
INSERT INTO metodos_pago (nombre, tipo, orden) VALUES 
('Efectivo', 'efectivo', 1),
('Tarjeta Débito', 'tarjeta', 2),
('Tarjeta Crédito', 'tarjeta', 3),
('Transferencia', 'transferencia', 4),
('Crédito 30 días', 'credito', 5),
('Crédito 60 días', 'credito', 6);
