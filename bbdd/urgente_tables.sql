-- ============================================================
--  TABLAS PARA SECCIÓN URGENTE
--  Ejecutar sobre la base de datos bd_protectora
-- ============================================================
USE bd_protectora;

-- Publicaciones de mascotas perdidas / encontradas
CREATE TABLE IF NOT EXISTS PublicacionUrgente (
    id_publicacion    INT AUTO_INCREMENT PRIMARY KEY,
    tipo              ENUM('PERDIDO', 'ENCONTRADO') NOT NULL,
    nombre_animal     VARCHAR(100),
    especie           VARCHAR(50),
    descripcion       TEXT NOT NULL,
    foto              VARCHAR(255),
    nombre_contacto   VARCHAR(100),
    telefono_contacto VARCHAR(20),
    ciudad            VARCHAR(100),
    fecha             DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo            BOOLEAN DEFAULT TRUE
);

-- Comentarios en cada publicación urgente
CREATE TABLE IF NOT EXISTS ComentarioUrgente (
    id_comentario  INT AUTO_INCREMENT PRIMARY KEY,
    id_publicacion INT NOT NULL,
    texto          TEXT NOT NULL,
    nombre_autor   VARCHAR(100) NOT NULL,
    fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_comentario_publicacion
        FOREIGN KEY (id_publicacion)
        REFERENCES PublicacionUrgente(id_publicacion)
        ON DELETE CASCADE
);
