-- ============================================================
--  BASE DE DATOS: bd_protectora
--  Proyecto Final
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

-- Usuario
CREATE TABLE Usuario (
    id_adoptante  INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100) NOT NULL,
    apellido      VARCHAR(100),
    numero        VARCHAR(20) UNIQUE,
    fiabilidad    INT DEFAULT 0 CHECK (fiabilidad BETWEEN 0 AND 10),
    admin         BOOLEAN DEFAULT FALSE,
    contrasena    VARCHAR(255) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE
);

-- Colaborador
CREATE TABLE Colaborador (
    id_colaborador INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100) NOT NULL,
    telefono       VARCHAR(20) UNIQUE,
    web            VARCHAR(255),
    profesion      VARCHAR(100),
    suscripcion    VARCHAR(50),
    ubicacion      VARCHAR(255)
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

-- Galeria (depende de Animales)
CREATE TABLE Galeria (
    id_foto      INT AUTO_INCREMENT PRIMARY KEY,
    id_animal    INT,
    ruta         VARCHAR(255) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,

    CONSTRAINT fk_galeria_animal FOREIGN KEY (id_animal)
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


-- ------------------------------------------------------------
--  3. DATOS INICIALES
-- ------------------------------------------------------------

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


-- ------------------------------------------------------------
--  3. DATOS INICIALES
-- ------------------------------------------------------------

INSERT INTO EstadoAnimal (nombre) VALUES
    ('DISPONIBLE'),
    ('ADOPTADO'),
    ('RESERVADO'),
    ('EN_ACOGIDA');

-- Contraseñas: sustituir 'HASH_AQUI' por el resultado de password_hash('tu_pass', PASSWORD_BCRYPT)
INSERT INTO Protectora (nombre_protectora, email, contrasena, telefono, ciudad, localidad, direccion) VALUES
    ('Protectora Patitas Felices', 'patitas@gocatch.es', '$2y$10$uiStNAmfcJY5USoD/x.HQ.gnIaWJuaJYasFAdvS1/U5ZwYEwsogQ.', '600111222', 'Madrid',    'Vallecas',  'Calle Mayor 12'),
    ('Refugio Huellas del Sur',   'huellas@gocatch.es', '$2y$10$uiStNAmfcJY5USoD/x.HQ.gnIaWJuaJYasFAdvS1/U5ZwYEwsogQ.', '600333444', 'Sevilla',   'Triana',    'Avenida del Río 7');

INSERT INTO Usuario (nombre, apellido, contrasena, email, admin) VALUES
    ('Admin', 'Go Catch', '$2y$10$uiStNAmfcJY5USoD/x.HQ.gnIaWJuaJYasFAdvS1/U5ZwYEwsogQ.', 'admin@gocatch.es', TRUE);

-- Animales de prueba
-- id_estado: 1=DISPONIBLE 2=ADOPTADO 3=RESERVADO 4=EN_ACOGIDA
-- id_protectora: 1=Patitas Felices  2=Huellas del Sur
INSERT INTO Animales
    (id_protectora, id_estado, nombre, especie, raza, sexo, color, peso, edad,
     fecha_entrada, descripcion,
     compatibilidad_perros, compatibilidad_gatos, compatibilidad_ninos)
VALUES
    (1, 1, 'Luna',    'Perro', 'Labrador',        'H', 'Dorado',  28.5, 3, '2024-11-10', 'Muy cariñosa y activa. Le encanta jugar al aire libre.',                  1, 0, 1),
    (1, 1, 'Milo',    'Gato',  'Europeo',         'M', 'Naranja',  4.2, 2, '2024-12-01', 'Tranquilo y hogareño. Se lleva bien con otros gatos.',                    0, 1, 1),
    (1, 1, 'Rocky',   'Perro', 'Pastor Alemán',   'M', 'Negro',   32.0, 5, '2025-01-15', 'Leal y protector. Necesita espacio y ejercicio diario.',                  1, 0, 0),
    (2, 1, 'Nala',    'Gato',  'Siamés',          'H', 'Crema',    3.8, 1, '2025-02-20', 'Juguetona y curiosa. Se adapta bien a cualquier hogar.',                  0, 1, 1),
    (2, 1, 'Bruno',   'Perro', 'Bulldog Francés', 'M', 'Atigrado',11.0, 4, '2025-03-05', 'Tranquilo y muy sociable. Ideal para pisos pequeños.',                   1, 1, 1),
    (2, 1, 'Cleo',    'Perro', 'Mestizo',         'H', 'Marrón',  15.5, 2, '2025-03-18', 'Rescatada de la calle. Cariñosa y agradecida con quien la cuida.',       1, 0, 1);
