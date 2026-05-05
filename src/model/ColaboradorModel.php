<?php
class ColaboradorModel {
    private $conexion;

    public function __construct($conexion) {
        $this->conexion = $conexion;
    }

    public function getAll(): array {
        $sql = "SELECT * FROM Colaborador
                ORDER BY (suscripcion = 'premium') DESC, nombre ASC";
        $res = $this->conexion->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById(int $id): ?array {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM Colaborador WHERE id_colaborador = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function create(array $d): int|false {
        $stmt = $this->conexion->prepare(
            "INSERT INTO Colaborador (nombre, telefono, web, profesion, suscripcion, ubicacion, foto)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss",
            $d['nombre'], $d['telefono'], $d['web'],
            $d['profesion'], $d['suscripcion'], $d['ubicacion'], $d['foto']
        );
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    public function updateFoto(int $id, string $ruta): void {
        $stmt = $this->conexion->prepare(
            "UPDATE Colaborador SET foto = ? WHERE id_colaborador = ?"
        );
        $stmt->bind_param("si", $ruta, $id);
        $stmt->execute();
        $stmt->close();
    }

    public function getDestacados(int $limit = 4): array {
        $stmt = $this->conexion->prepare(
            "SELECT * FROM Colaborador
             ORDER BY (suscripcion = 'premium') DESC, nombre ASC
             LIMIT ?"
        );
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
