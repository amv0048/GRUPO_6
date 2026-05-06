<?php
class CrowdfundingModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    private function safePrep(string $sql): ?mysqli_stmt {
        $st = $this->db->prepare($sql);
        if ($st === false) {
            error_log("CrowdfundingModel::prepare() failed — " . $this->db->error . " — SQL: " . $sql);
        }
        return $st !== false ? $st : null;
    }

    public function getCasosActivos(int $limit = 6): array {
        $res = $this->db->query(
            "SELECT c.id_caso, c.titulo, c.animal_nombre, c.descripcion,
                    c.meta_euros, c.recaudado, c.foto, p.nombre_protectora
             FROM CrowdfundingCaso c
             JOIN Protectora p ON c.id_protectora = p.id_protectora
             WHERE c.activo = 1
             ORDER BY c.fecha_creacion DESC
             LIMIT $limit"
        );
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getCasosByProtectora(int $id_protectora): array {
        $st = $this->safePrep(
            "SELECT id_caso, titulo, animal_nombre, descripcion, meta_euros, recaudado, activo, foto
             FROM CrowdfundingCaso WHERE id_protectora = ? ORDER BY fecha_creacion DESC"
        );
        if (!$st) return [];
        $st->bind_param('i', $id_protectora);
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCasoById(int $id_caso): ?array {
        $st = $this->safePrep("SELECT * FROM CrowdfundingCaso WHERE id_caso = ?");
        if (!$st) return null;
        $st->bind_param('i', $id_caso);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function crear(int $id_protectora, string $titulo, string $animal, string $desc, float $meta, string $foto): bool {
        $st = $this->safePrep(
            "INSERT INTO CrowdfundingCaso
                (id_protectora, titulo, animal_nombre, descripcion, meta_euros, foto)
             VALUES (?,?,?,?,?,?)"
        );
        if (!$st) return false;
        $st->bind_param('isssds', $id_protectora, $titulo, $animal, $desc, $meta, $foto);
        return $st->execute();
    }

    public function crearTemp(int $id_protectora, string $titulo, string $animal, string $desc, float $meta) {
        $st = $this->safePrep(
            "INSERT INTO CrowdfundingCaso
                (id_protectora, titulo, animal_nombre, descripcion, meta_euros, foto)
             VALUES (?,?,?,?,?,?)"
        );
        if (!$st) return false;
        $foto = '';
        $st->bind_param('isssds', $id_protectora, $titulo, $animal, $desc, $meta, $foto);
        if (!$st->execute()) return false;
        return $this->db->insert_id;
    }

    public function actualizarFoto(int $id_caso, int $id_protectora, string $foto): bool {
        $st = $this->safePrep(
            "UPDATE CrowdfundingCaso SET foto=? WHERE id_caso=? AND id_protectora=?"
        );
        if (!$st) return false;
        $st->bind_param('sii', $foto, $id_caso, $id_protectora);
        return $st->execute();
    }

    public function actualizar(int $id_caso, int $id_protectora, string $titulo, string $animal, string $desc, float $meta, string $foto): bool {
        $st = $this->safePrep(
            "UPDATE CrowdfundingCaso
             SET titulo=?, animal_nombre=?, descripcion=?, meta_euros=?, foto=?
             WHERE id_caso=? AND id_protectora=?"
        );
        if (!$st) return false;
        $st->bind_param('sssdsii', $titulo, $animal, $desc, $meta, $foto, $id_caso, $id_protectora);
        return $st->execute();
    }

    public function eliminar(int $id_caso, int $id_protectora): bool {
        $st = $this->safePrep(
            "DELETE FROM CrowdfundingCaso WHERE id_caso=? AND id_protectora=?"
        );
        if (!$st) return false;
        $st->bind_param('ii', $id_caso, $id_protectora);
        return $st->execute();
    }

    public function toggleActivo(int $id_caso, int $id_protectora): bool {
        $st = $this->safePrep(
            "UPDATE CrowdfundingCaso SET activo = NOT activo WHERE id_caso=? AND id_protectora=?"
        );
        if (!$st) return false;
        $st->bind_param('ii', $id_caso, $id_protectora);
        return $st->execute();
    }

    public function addDonacion(int $id_caso, float $cantidad, string $nombre, ?int $id_adoptante): bool {
        $st = $this->safePrep(
            "INSERT INTO Donacion (id_caso, id_adoptante, nombre_donante, cantidad) VALUES (?, ?, ?, ?)"
        );
        if (!$st) return false;
        $st->bind_param('iisd', $id_caso, $id_adoptante, $nombre, $cantidad);
        if (!$st->execute()) return false;
        $upd = $this->safePrep(
            "UPDATE CrowdfundingCaso SET recaudado = recaudado + ? WHERE id_caso = ?"
        );
        if (!$upd) return false;
        $upd->bind_param('di', $cantidad, $id_caso);
        return $upd->execute();
    }

    public function addDonacionDirecta(int $id_protectora, ?int $id_adoptante, string $nombre, float $cantidad): bool {
        $st = $this->safePrep(
            "INSERT INTO DonacionDirecta (id_protectora, id_adoptante, nombre_donante, cantidad)
             VALUES (?, ?, ?, ?)"
        );
        if (!$st) return false;
        $st->bind_param('iisd', $id_protectora, $id_adoptante, $nombre, $cantidad);
        return $st->execute();
    }
}
