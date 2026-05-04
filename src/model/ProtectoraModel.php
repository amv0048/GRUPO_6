<?php
class ProtectoraModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getAll(): array {
        $res = $this->db->query(
            "SELECT id_protectora, nombre_protectora, ciudad, localidad, direccion, telefono, logo
             FROM Protectora ORDER BY nombre_protectora"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Protectora WHERE id_protectora = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function update(int $id, array $campos, array $valores, string $tipos): bool {
        $valores[] = $id;
        $tipos .= "i";
        $sql  = "UPDATE Protectora SET " . implode(", ", $campos) . " WHERE id_protectora = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Protectora WHERE id_protectora = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function verificarExiste(int $id): bool {
        $stmt = $this->db->prepare("SELECT id_protectora FROM Protectora WHERE id_protectora = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
}
