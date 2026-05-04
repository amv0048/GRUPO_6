<?php
class LikeModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getLikesByAdoptante(int $id_adoptante): array {
        $uid = (int)$id_adoptante;
        $res = $this->db->query("SELECT id_animal FROM Likes WHERE id_adoptante = $uid");
        $ids = [];
        if ($res) while ($r = $res->fetch_assoc()) $ids[] = (int)$r['id_animal'];
        return $ids;
    }

    public function existe(int $id_adoptante, int $id_animal): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM Likes WHERE id_adoptante = ? AND id_animal = ?"
        );
        $stmt->bind_param("ii", $id_adoptante, $id_animal);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function add(int $id_adoptante, int $id_animal): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO Likes (id_adoptante, id_animal) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $id_adoptante, $id_animal);
        return $stmt->execute();
    }

    public function remove(int $id_adoptante, int $id_animal): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM Likes WHERE id_adoptante = ? AND id_animal = ?"
        );
        $stmt->bind_param("ii", $id_adoptante, $id_animal);
        return $stmt->execute();
    }

    public function toggle(int $id_adoptante, int $id_animal): bool {
        if ($this->existe($id_adoptante, $id_animal)) {
            $this->remove($id_adoptante, $id_animal);
            return false;
        }
        $this->add($id_adoptante, $id_animal);
        return true;
    }
}
