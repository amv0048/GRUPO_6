-- ============================================================
--  BASE DE DATOS: bd_protectora
--  Proyecto Final — Go Catch
-- ============================================================

DROP DATABASE IF EXISTS bd_protectora;
CREATE DATABASE IF NOT EXISTS bd_protectora
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;
USE bd_protectora;


-- ------------------------------------------------------------
--  1. TABLAS INDEPENDIENTES (sin FKs)
-- ------------------------------------------------------------

-- Estado del animal (tabla de referencia dinámica)
CREATE TABLE EstadoAnimal (
    id_estado   INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(50) NOT NULL UNIQUE
);

-- Protectora
CREATE TABLE Protectora (
    id_protectora     INT AUTO_INCREMENT PRIMARY KEY,
    nombre_protectora VARCHAR(100) NOT NULL,
    email             VARCHAR(100) NOT NULL UNIQUE,
    contrasena        VARCHAR(255) NOT NULL,
    telefono          VARCHAR(20),
    ciudad            VARCHAR(100),
    localidad         VARCHAR(100),
    direccion         VARCHAR(255),
    logo              VARCHAR(255)
);

-- Usuario (adoptante)
CREATE TABLE Usuario (
    id_adoptante  INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    apellido      VARCHAR(100),
    numero        VARCHAR(20) UNIQUE,
    fiabilidad    INT DEFAULT 0 CHECK (fiabilidad BETWEEN 0 AND 10),
    admin         BOOLEAN DEFAULT FALSE,
    contrasena    VARCHAR(255) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    baneado       BOOLEAN NOT NULL DEFAULT FALSE
);

-- Historial de baneos
CREATE TABLE IF NOT EXISTS Baneos (
    id_baneo       INT AUTO_INCREMENT PRIMARY KEY,
    id_adoptante   INT NOT NULL,
    motivo_tipo    VARCHAR(100) NOT NULL,
    motivo_detalle TEXT,
    fecha_baneo    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_admin       INT NOT NULL,
    CONSTRAINT fk_baneo_usuario FOREIGN KEY (id_adoptante)
        REFERENCES Usuario(id_adoptante) ON DELETE CASCADE,
    CONSTRAINT fk_baneo_admin FOREIGN KEY (id_admin)
        REFERENCES Usuario(id_adoptante) ON DELETE CASCADE
);

-- Colaborador
CREATE TABLE Colaborador (
    id_colaborador INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    telefono       VARCHAR(20) UNIQUE,
    web            VARCHAR(255),
    profesion      VARCHAR(100),
    suscripcion    VARCHAR(50),
    ubicacion      VARCHAR(255),
    foto           VARCHAR(255)
);


-- ------------------------------------------------------------
--  2. TABLAS CON DEPENDENCIAS
-- ------------------------------------------------------------

-- Animales (depende de Protectora y EstadoAnimal)
CREATE TABLE Animales (
    id_animal              INT AUTO_INCREMENT PRIMARY KEY,
    id_protectora          INT,
    nombre                 VARCHAR(100) NOT NULL,
    id_estado              INT,
    especie                VARCHAR(50),
    raza                   VARCHAR(50),
    sexo                   CHAR(1),
    color                  VARCHAR(50),
    peso                   DECIMAL(5,2),
    edad                   INT,
    fecha_entrada          DATE,
    descripcion            TEXT,
    compatibilidad_perros  BOOLEAN DEFAULT FALSE,
    compatibilidad_gatos   BOOLEAN DEFAULT FALSE,
    compatibilidad_ninos   BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_animal_protectora FOREIGN KEY (id_protectora)
        REFERENCES Protectora(id_protectora) ON DELETE CASCADE,
    CONSTRAINT fk_animal_estado FOREIGN KEY (id_estado)
        REFERENCES EstadoAnimal(id_estado) ON DELETE SET NULL
);

-- Galería de fotos (depende de Animales)
CREATE TABLE Galeria (
    id_foto      INT AUTO_INCREMENT PRIMARY KEY,
    id_animal    INT,
    ruta         VARCHAR(255) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,
    CONSTRAINT fk_galeria_animal FOREIGN KEY (id_animal)
        REFERENCES Animales(id_animal) ON DELETE CASCADE
);

-- Likes (depende de Usuario y Animales)
CREATE TABLE Likes (
    id_adoptante INT,
    id_animal    INT,
    fecha        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_adoptante, id_animal),
    CONSTRAINT fk_like_adoptante FOREIGN KEY (id_adoptante)
        REFERENCES Usuario(id_adoptante) ON DELETE CASCADE,
    CONSTRAINT fk_like_animal FOREIGN KEY (id_animal)
        REFERENCES Animales(id_animal) ON DELETE CASCADE
);

-- Adopciones (depende de Usuario y Animales)
CREATE TABLE Adopciones (
    id_adopcion  INT AUTO_INCREMENT PRIMARY KEY,
    id_adoptante INT,
    id_animal    INT,
    fecha        DATE,
    operacion    VARCHAR(100),
    contrato     VARCHAR(255),
    CONSTRAINT fk_adopcion_adoptante FOREIGN KEY (id_adoptante)
        REFERENCES Usuario(id_adoptante) ON DELETE SET NULL,
    CONSTRAINT fk_adopcion_animal FOREIGN KEY (id_animal)
        REFERENCES Animales(id_animal) ON DELETE CASCADE
);

-- Solicitudes de adopción (depende de Animales y Usuario)
CREATE TABLE IF NOT EXISTS SolicitudAdopcion (
    id_solicitud       INT AUTO_INCREMENT PRIMARY KEY,
    id_animal          INT NOT NULL,
    id_adoptante       INT NOT NULL,
    fecha_solicitud    DATETIME DEFAULT CURRENT_TIMESTAMP,
    nombre             VARCHAR(100) NOT NULL,
    apellido           VARCHAR(100) NOT NULL,
    email              VARCHAR(150) NOT NULL,
    telefono           VARCHAR(20),
    dni                VARCHAR(20),
    tipo_vivienda      ENUM('piso','casa','chalet','otro') NOT NULL,
    tiene_jardin       TINYINT(1) DEFAULT 0,
    metros_vivienda    VARCHAR(20),
    tiene_ninos        TINYINT(1) DEFAULT 0,
    edades_ninos       VARCHAR(100),
    tiene_animales     TINYINT(1) DEFAULT 0,
    desc_animales      TEXT,
    horas_solo         TINYINT UNSIGNED,
    experiencia        TINYINT(1) DEFAULT 0,
    motivacion         TEXT NOT NULL,
    acepta_visita      TINYINT(1) DEFAULT 0,
    acepta_seguimiento TINYINT(1) DEFAULT 0,
    estado_solicitud   ENUM('PENDIENTE','APROBADA','RECHAZADA') DEFAULT 'PENDIENTE',
    FOREIGN KEY (id_animal)    REFERENCES Animales(id_animal)    ON DELETE CASCADE,
    FOREIGN KEY (id_adoptante) REFERENCES Usuario(id_adoptante)  ON DELETE CASCADE
);

-- Slots de disponibilidad de protectoras para citas
CREATE TABLE IF NOT EXISTS DisponibilidadProtectora (
    id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
    id_protectora     INT NOT NULL,
    fecha             DATE NOT NULL,
    hora_inicio       TIME NOT NULL,
    hora_fin          TIME NOT NULL,
    disponible        TINYINT(1) DEFAULT 1,
    UNIQUE KEY uniq_slot (id_protectora, fecha, hora_inicio),
    FOREIGN KEY (id_protectora) REFERENCES Protectora(id_protectora) ON DELETE CASCADE
);

-- Citas de entrevista para adopción (depende de SolicitudAdopcion y DisponibilidadProtectora)
CREATE TABLE IF NOT EXISTS CitaEntrevista (
    id_cita           INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud      INT NOT NULL,
    id_disponibilidad INT NOT NULL,
    fecha_reserva     DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado            ENUM('PENDIENTE','CONFIRMADA','CANCELADA') DEFAULT 'PENDIENTE',
    notas             TEXT,
    FOREIGN KEY (id_solicitud)      REFERENCES SolicitudAdopcion(id_solicitud)           ON DELETE CASCADE,
    FOREIGN KEY (id_disponibilidad) REFERENCES DisponibilidadProtectora(id_disponibilidad) ON DELETE CASCADE
);


-- ------------------------------------------------------------
--  3. SECCIÓN URGENTE (mascotas perdidas / encontradas)
-- ------------------------------------------------------------

-- Publicaciones de mascotas perdidas o encontradas
CREATE TABLE IF NOT EXISTS PublicacionUrgente (
    id_publicacion    INT AUTO_INCREMENT PRIMARY KEY,
    tipo              ENUM('PERDIDO','ENCONTRADO') NOT NULL,
    nombre_animal     VARCHAR(100),
    especie           VARCHAR(50),
    descripcion       TEXT NOT NULL,
    foto              VARCHAR(255),
    nombre_contacto   VARCHAR(100),
    telefono_contacto VARCHAR(20),
    ciudad            VARCHAR(100),
    fecha             DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo            BOOLEAN DEFAULT TRUE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Comentarios en publicaciones urgentes
CREATE TABLE IF NOT EXISTS ComentarioUrgente (
    id_comentario  INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    texto          TEXT NOT NULL,
    nombre_autor   VARCHAR(100) NOT NULL,
    fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comentario_pub FOREIGN KEY (id_publicacion)
        REFERENCES PublicacionUrgente(id_publicacion) ON DELETE CASCADE
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- ------------------------------------------------------------
--  4. CROWDFUNDING Y DONACIONES
-- ------------------------------------------------------------

-- Casos de crowdfunding publicados por protectoras
CREATE TABLE IF NOT EXISTS CrowdfundingCaso (
    id_caso        INT AUTO_INCREMENT PRIMARY KEY,
    id_protectora  INT NOT NULL,
    titulo         VARCHAR(200) NOT NULL,
    animal_nombre  VARCHAR(100),
    descripcion    TEXT NOT NULL,
    meta_euros     DECIMAL(10,2) NOT NULL,
    recaudado      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    activo         TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    foto           VARCHAR(255),
    CONSTRAINT fk_crowd_protectora FOREIGN KEY (id_protectora)
        REFERENCES Protectora(id_protectora) ON DELETE CASCADE
);

-- Donaciones a casos de crowdfunding
CREATE TABLE IF NOT EXISTS Donacion (
    id_donacion    INT AUTO_INCREMENT PRIMARY KEY,
    id_caso        INT NOT NULL,
    id_adoptante   INT DEFAULT NULL,
    nombre_donante VARCHAR(100),
    cantidad       DECIMAL(8,2) NOT NULL,
    fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_don_caso FOREIGN KEY (id_caso)
        REFERENCES CrowdfundingCaso(id_caso) ON DELETE CASCADE,
    CONSTRAINT fk_don_adoptante FOREIGN KEY (id_adoptante)
        REFERENCES Usuario(id_adoptante) ON DELETE SET NULL
);

-- Donaciones directas a protectoras
CREATE TABLE IF NOT EXISTS DonacionDirecta (
    id_donacion    INT AUTO_INCREMENT PRIMARY KEY,
    id_protectora  INT NOT NULL,
    id_adoptante   INT DEFAULT NULL,
    nombre_donante VARCHAR(100),
    cantidad       DECIMAL(8,2) NOT NULL,
    fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_protectora) REFERENCES Protectora(id_protectora) ON DELETE CASCADE,
    FOREIGN KEY (id_adoptante)  REFERENCES Usuario(id_adoptante)     ON DELETE SET NULL
);


-- ------------------------------------------------------------
--  5. DATOS INICIALES
-- ------------------------------------------------------------

INSERT INTO EstadoAnimal (nombre) VALUES
    ('DISPONIBLE'),
    ('ADOPTADO'),
    ('RESERVADO'),
    ('EN_ACOGIDA');

-- Contraseña de ejemplo: A12345%
-- Sustituir el hash por password_hash('tu_pass', PASSWORD_BCRYPT) para producción
INSERT INTO Protectora (nombre_protectora, email, contrasena, telefono, ciudad, localidad, direccion) VALUES
    ('Protectora Patitas Felices', 'patitas@gocatch.es', '$2y$10$qLN1KwouQRfabjOqpXlgkui3Xoi7zYMauDH7cSvBrA7LUJu5P8bjO', '600111222', 'Madrid',  'Vallecas', 'Calle Mayor 12'),
    ('Refugio Huellas del Sur',   'huellas@gocatch.es', '$2y$10$qLN1KwouQRfabjOqpXlgkui3Xoi7zYMauDH7cSvBrA7LUJu5P8bjO', '600333444', 'Sevilla', 'Triana',   'Avenida del Río 7');

INSERT INTO Usuario (nombre, apellido, contrasena, email, admin) VALUES
    ('Admin', 'Go Catch', '$2y$10$qLN1KwouQRfabjOqpXlgkui3Xoi7zYMauDH7cSvBrA7LUJu5P8bjO', 'admin@gocatch.es', TRUE);

-- Colaboradores de prueba
INSERT INTO Colaborador (nombre, telefono, web, profesion, suscripcion, ubicacion) VALUES
    ('Clínica Veterinaria Huella Verde',        '600100201', 'https://huellaverde.es',           'Veterinaria',            'premium', 'Sevilla'),
    ('Carlos López – Adiestramiento Canino',    '600100202', 'https://carlosadiestramiento.es',  'Adiestramiento',         'premium', 'Madrid'),
    ('Peluquería Animal Bigotes',               '600100203', NULL,                               'Peluquería canina',      'basica',  'Valencia'),
    ('Guardería Pet Paradise',                  '600100204', 'https://petparadise.es',           'Guardería de animales',  'basica',  'Barcelona'),
    ('Dra. Ana García – Medicina Holística',    '600100205', NULL,                               'Medicina alternativa',   'basica',  'Granada'),
    ('Pablo Ruiz – Fotografía de Mascotas',     '600100206', 'https://fotopablo.es',             'Fotografía de animales', 'basica',  'Málaga');

-- Animales de prueba
-- id_estado: 1=DISPONIBLE  2=ADOPTADO  3=RESERVADO  4=EN_ACOGIDA
-- id_protectora: 1=Patitas Felices  2=Huellas del Sur
INSERT INTO Animales
    (id_protectora, id_estado, nombre, especie, raza, sexo, color, peso, edad,
     fecha_entrada, descripcion,
     compatibilidad_perros, compatibilidad_gatos, compatibilidad_ninos)
VALUES
    (1, 1, 'Luna',  'Perro', 'Labrador',        'H', 'Dorado',   28.5, 3, '2024-11-10', 'Muy cariñosa y activa. Le encanta jugar al aire libre.',             1, 0, 1),
    (1, 1, 'Milo',  'Gato',  'Europeo',         'M', 'Naranja',   4.2, 2, '2024-12-01', 'Tranquilo y hogareño. Se lleva bien con otros gatos.',               0, 1, 1),
    (1, 1, 'Rocky', 'Perro', 'Pastor Alemán',   'M', 'Negro',    32.0, 5, '2025-01-15', 'Leal y protector. Necesita espacio y ejercicio diario.',             1, 0, 0),
    (2, 1, 'Nala',  'Gato',  'Siamés',          'H', 'Crema',     3.8, 1, '2025-02-20', 'Juguetona y curiosa. Se adapta bien a cualquier hogar.',             0, 1, 1),
    (2, 1, 'Bruno', 'Perro', 'Bulldog Francés', 'M', 'Atigrado', 11.0, 4, '2025-03-05', 'Tranquilo y muy sociable. Ideal para pisos pequeños.',              1, 1, 1),
    (2, 1, 'Cleo',  'Perro', 'Mestizo',         'H', 'Marrón',   15.5, 2, '2025-03-18', 'Rescatada de la calle. Cariñosa y agradecida con quien la cuida.', 1, 0, 1);
