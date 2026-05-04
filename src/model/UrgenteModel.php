<?php
class UrgenteModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getPublicaciones(string $filtro = ''): array {
        $sql = "SELECT p.*,
                    (SELECT COUNT(*) FROM ComentarioUrgente c
                     WHERE c.id_publicacion = p.id_publicacion) AS total_comentarios
                FROM PublicacionUrgente p
                WHERE p.activo = 1";
        if ($filtro) {
            $f = $this->db->real_escape_string($filtro);
            $sql .= " AND p.tipo = '$f'";
        }
        $sql .= " ORDER BY p.fecha DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getComentarios(array $ids): array {
        if (empty($ids)) return [];
        $in  = implode(",", array_map('intval', $ids));
        $res = $this->db->query(
            "SELECT * FROM ComentarioUrgente WHERE id_publicacion IN ($in) ORDER BY fecha ASC"
        );
        $map = [];
        if ($res) {
            foreach ($res->fetch_all(MYSQLI_ASSOC) as $c) {
                $map[$c['id_publicacion']][] = $c;
            }
        }
        return $map;
    }

    public function createPublicacion(array $d): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO PublicacionUrgente
                (tipo, nombre_animal, especie, descripcion, foto,
                 nombre_contacto, telefono_contacto, ciudad)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("ssssssss",
            $d['tipo'], $d['nombre'], $d['especie'], $d['descripcion'],
            $d['foto'], $d['contacto'], $d['telefono'], $d['ciudad']
        );
        return $stmt->execute();
    }

    public function addComentario(int $id_publicacion, string $texto, string $nombre_autor): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO ComentarioUrgente (id_publicacion, texto, nombre_autor) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $id_publicacion, $texto, $nombre_autor);
        return $stmt->execute();
    }
}
