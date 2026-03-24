-- ============================================================
--  BASE DE DATOS: bd_protectora
--  Proyecto Final
-- ============================================================

-- DROP USER IF EXISTS 'MEDAC'@'localhost';

-- CREATE USER 'MEDAC'@'localhost' IDENTIFIED BY 'MEDAC';
-- GRANT ALL PRIVILEGES ON *.* TO 'MEDAC'@'localhost';
-- FLUSH PRIVILEGES;


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
    telefono          VARCHAR(20),
    ciudad            VARCHAR(100),
    localidad         VARCHAR(100),
    direccion         VARCHAR(255),
    logo              VARCHAR(255),
    contrasena        VARCHAR(255)
);

-- Usuario
CREATE TABLE Usuario (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    apellido    VARCHAR(100),
    numero      VARCHAR(20) UNIQUE,
    fiabilidad  INT DEFAULT 0 CHECK (fiabilidad BETWEEN 0 AND 10),
    admin       BOOLEAN DEFAULT FALSE,
    contraseña  VARCHAR(255) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE
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
    id_estado              INT,
    especie                VARCHAR(50),
    raza                   VARCHAR(50),
    sexo                   CHAR(1),          -- 'M' o 'H'
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
    id_foto    INT AUTO_INCREMENT PRIMARY KEY,
    id_animal  INT,
    ruta       VARCHAR(255) NOT NULL,
    es_principal BOOLEAN DEFAULT FALSE,

    CONSTRAINT fk_galeria_animal FOREIGN KEY (id_animal)
        REFERENCES Animales(id_animal) ON DELETE CASCADE
);

-- Adopciones (depende de Usuario y Animales)
CREATE TABLE Adopciones (
    id_adopcion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario  INT,
    id_animal   INT,
    fecha       DATE,
    operacion   VARCHAR(100),
    contrato    VARCHAR(255),

    CONSTRAINT fk_adopcion_usuario FOREIGN KEY (id_usuario)
        REFERENCES Usuario(id) ON DELETE SET NULL,

    CONSTRAINT fk_adopcion_animal FOREIGN KEY (id_animal)
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