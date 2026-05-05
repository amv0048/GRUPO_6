<?php
require_once __DIR__ . '/../helpers/media.php';

class ProtectoraModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getAll(): array {
        $res = $this->db->query(
            "SELECT id_protectora, nombre_protectora, ciudad, localidad, direccion, telefono, logo
             FROM Protectora ORDER BY nombre_protectora"
        );
        if (!$res) {
            return [];
        }

        $rows = $res->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$row) {
            $row['logo'] = media_normalize_url($row['logo']);
        }
        unset($row);
        return $rows;
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Protectora WHERE id_protectora = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: null;
        if ($row && array_key_exists('logo', $row)) {
            $row['logo'] = media_normalize_url($row['logo']);
        }
        return $row;
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
