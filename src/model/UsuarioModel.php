<?php
class UsuarioModel {
    private mysqli $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM Usuario WHERE id_adoptante = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getBasico(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT nombre, apellido, email, numero FROM Usuario WHERE id_adoptante = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function update(int $id, array $campos, array $valores, string $tipos): bool {
        $valores[] = $id;
        $tipos .= "i";
        $sql  = "UPDATE Usuario SET " . implode(", ", $campos) . " WHERE id_adoptante = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($tipos, ...$valores);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM Usuario WHERE id_adoptante = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function buscar(string $busqueda): array {
        if ($busqueda !== '') {
            $like = "%$busqueda%";
            $stmt = $this->db->prepare(
                "SELECT id_adoptante, nombre, apellido, email, admin, baneado
                 FROM Usuario
                 WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ?
                 ORDER BY nombre ASC"
            );
            $stmt->bind_param("sss", $like, $like, $like);
        } else {
            $stmt = $this->db->prepare(
                "SELECT id_adoptante, nombre, apellido, email, admin, baneado
                 FROM Usuario ORDER BY nombre ASC"
            );
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getParaBanear(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id_adoptante, nombre, apellido, email, admin, baneado
             FROM Usuario WHERE id_adoptante = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function banear(int $id_objetivo, string $motivo_tipo, string $motivo_detalle, int $id_admin): bool {
        $upd = $this->db->prepare("UPDATE Usuario SET baneado = TRUE WHERE id_adoptante = ?");
        $upd->bind_param("i", $id_objetivo);
        if (!$upd->execute()) return false;
        $upd->close();
        $ins = $this->db->prepare(
            "INSERT INTO Baneos (id_adoptante, motivo_tipo, motivo_detalle, id_admin) VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("issi", $id_objetivo, $motivo_tipo, $motivo_detalle, $id_admin);
        return $ins->execute();
    }
}
