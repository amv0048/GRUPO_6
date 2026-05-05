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
