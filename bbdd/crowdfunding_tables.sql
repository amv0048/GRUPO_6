USE bd_protectora;

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
