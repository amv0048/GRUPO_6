<?php
class AnimalModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getDisponibles(array $f): array {
        $sql = "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.edad, a.sexo,
                       g.ruta AS foto, p.nombre_protectora, p.ciudad
                FROM Animales a
                JOIN EstadoAnimal e ON a.id_estado = e.id_estado
                JOIN Protectora p   ON a.id_protectora = p.id_protectora
                LEFT JOIN Galeria g ON a.id_animal = g.id_animal AND g.es_principal = 1
                WHERE e.nombre = 'DISPONIBLE'";
        $params = []; $types = '';
        if (!empty($f['especie']))    { $sql .= " AND a.especie = ?"; $params[] = $f['especie'];    $types .= 's'; }
        if (!empty($f['ciudad']))     { $sql .= " AND p.ciudad = ?";  $params[] = $f['ciudad'];     $types .= 's'; }
        if (!empty($f['raza']))       { $sql .= " AND a.raza = ?";    $params[] = $f['raza'];       $types .= 's'; }
        if (!empty($f['sexo']))       { $sql .= " AND a.sexo = ?";    $params[] = $f['sexo'];       $types .= 's'; }
        if (!empty($f['color']))      { $sql .= " AND a.color = ?";   $params[] = $f['color'];      $types .= 's'; }
        if ($f['edad_min'] !== '')    { $sql .= " AND a.edad >= ?";   $params[] = $f['edad_min'];   $types .= 'i'; }
        if ($f['edad_max'] !== '')    { $sql .= " AND a.edad <= ?";   $params[] = $f['edad_max'];   $types .= 'i'; }
        if ($f['peso_min'] !== '')    { $sql .= " AND a.peso >= ?";   $params[] = $f['peso_min'];   $types .= 'd'; }
        if ($f['peso_max'] !== '')    { $sql .= " AND a.peso <= ?";   $params[] = $f['peso_max'];   $types .= 'd'; }
        if (!empty($f['compat_perros'])) $sql .= " AND a.compatibilidad_perros = 1";
        if (!empty($f['compat_gatos']))  $sql .= " AND a.compatibilidad_gatos = 1";
        if (!empty($f['compat_ninos']))  $sql .= " AND a.compatibilidad_ninos = 1";
        $sql .= " ORDER BY a.fecha_entrada DESC LIMIT 10";
        if ($params) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getByProtectora(int $id_protectora, array $f): array {
        $sql = "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.edad, a.sexo,
                       g.ruta AS foto, p.nombre_protectora, p.ciudad, e.nombre AS estado
                FROM Animales a
                JOIN EstadoAnimal e ON a.id_estado = e.id_estado
                JOIN Protectora p   ON a.id_protectora = p.id_protectora
                LEFT JOIN Galeria g ON a.id_animal = g.id_animal AND g.es_principal = 1
                WHERE a.id_protectora = ?";
        $params = [$id_protectora]; $types = 'i';
        if (!empty($f['especie']))    { $sql .= " AND a.especie = ?"; $params[] = $f['especie'];    $types .= 's'; }
        if (!empty($f['raza']))       { $sql .= " AND a.raza = ?";    $params[] = $f['raza'];       $types .= 's'; }
        if (!empty($f['sexo']))       { $sql .= " AND a.sexo = ?";    $params[] = $f['sexo'];       $types .= 's'; }
        if (!empty($f['color']))      { $sql .= " AND a.color = ?";   $params[] = $f['color'];      $types .= 's'; }
        if ($f['edad_min'] !== '')    { $sql .= " AND a.edad >= ?";   $params[] = $f['edad_min'];   $types .= 'i'; }
        if ($f['edad_max'] !== '')    { $sql .= " AND a.edad <= ?";   $params[] = $f['edad_max'];   $types .= 'i'; }
        if ($f['peso_min'] !== '')    { $sql .= " AND a.peso >= ?";   $params[] = $f['peso_min'];   $types .= 'd'; }
        if ($f['peso_max'] !== '')    { $sql .= " AND a.peso <= ?";   $params[] = $f['peso_max'];   $types .= 'd'; }
        if (!empty($f['compat_perros'])) $sql .= " AND a.compatibilidad_perros = 1";
        if (!empty($f['compat_gatos']))  $sql .= " AND a.compatibilidad_gatos = 1";
        if (!empty($f['compat_ninos']))  $sql .= " AND a.compatibilidad_ninos = 1";
        $sql .= " ORDER BY a.fecha_entrada DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.sexo, a.color,
                    a.peso, a.edad, a.fecha_entrada, a.descripcion,
                    a.compatibilidad_perros, a.compatibilidad_gatos, a.compatibilidad_ninos,
                    e.nombre AS estado,
                    p.nombre_protectora, p.ciudad, p.localidad, p.telefono,
                    p.email AS email_protectora, p.logo
             FROM Animales a
             LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
             LEFT JOIN Protectora   p ON a.id_protectora = p.id_protectora
             WHERE a.id_animal = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getForSolicitud(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.sexo, a.edad, a.id_protectora,
                    a.id_estado, e.nombre AS estado,
                    p.nombre_protectora, p.email AS email_protectora, p.telefono AS tel_protectora,
                    (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal
                     ORDER BY g.es_principal DESC, g.id_foto ASC LIMIT 1) AS foto
             FROM Animales a
             LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
             LEFT JOIN Protectora   p ON a.id_protectora = p.id_protectora
             WHERE a.id_animal = ?"
        );
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getAllByProtectora(int $id_protectora): array {
        $stmt = $this->db->prepare(
            "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.sexo, a.color, a.peso, a.edad,
                    a.fecha_entrada, a.descripcion,
                    a.compatibilidad_perros, a.compatibilidad_gatos, a.compatibilidad_ninos,
                    e.nombre AS estado, g.ruta AS foto
             FROM Animales a
             LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
             LEFT JOIN Galeria g ON a.id_animal = g.id_animal AND g.es_principal = 1
             WHERE a.id_protectora = ?
             ORDER BY a.id_animal DESC"
        );
        $stmt->bind_param("i", $id_protectora);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getByIdYProtectora(int $id_animal, int $id_protectora): ?array {
        $stmt = $this->db->prepare(
            "SELECT a.*, e.id_estado AS estado_actual
             FROM Animales a
             LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
             WHERE a.id_animal = ? AND a.id_protectora = ?"
        );
        $stmt->bind_param("ii", $id_animal, $id_protectora);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function create(array $d, int $id_protectora) {
        $stmt = $this->db->prepare(
            "INSERT INTO Animales
                (id_protectora, id_estado, nombre, especie, raza, sexo, color, peso, edad,
                 fecha_entrada, descripcion,
                 compatibilidad_perros, compatibilidad_gatos, compatibilidad_ninos)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iisssssdissiii",
            $id_protectora, $d['id_estado'], $d['nombre'], $d['especie'], $d['raza'], $d['sexo'],
            $d['color'], $d['peso'], $d['edad'], $d['fecha_entrada'], $d['descripcion'],
            $d['compat_perros'], $d['compat_gatos'], $d['compat_ninos']
        );
        return $stmt->execute() ? $this->db->insert_id : false;
    }

    public function update(array $d, int $id_animal, int $id_protectora): bool {
        $stmt = $this->db->prepare(
            "UPDATE Animales SET
                id_estado = ?, nombre = ?, especie = ?, raza = ?, sexo = ?, color = ?,
                peso = ?, edad = ?, fecha_entrada = ?, descripcion = ?,
                compatibilidad_perros = ?, compatibilidad_gatos = ?, compatibilidad_ninos = ?
             WHERE id_animal = ? AND id_protectora = ?"
        );
        $stmt->bind_param(
            "isssssdissiiiii",
            $d['id_estado'], $d['nombre'], $d['especie'], $d['raza'], $d['sexo'], $d['color'],
            $d['peso'], $d['edad'], $d['fecha_entrada'], $d['descripcion'],
            $d['compat_perros'], $d['compat_gatos'], $d['compat_ninos'],
            $id_animal, $id_protectora
        );
        return $stmt->execute();
    }

    public function delete(int $id_animal, int $id_protectora): bool {
        $chk = $this->db->prepare("SELECT id_animal FROM Animales WHERE id_animal = ? AND id_protectora = ?");
        $chk->bind_param("ii", $id_animal, $id_protectora);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) return false;
        $chk->close();
        $del = $this->db->prepare("DELETE FROM Animales WHERE id_animal = ?");
        $del->bind_param("i", $id_animal);
        return $del->execute();
    }

    public function getEstados(): array {
        $res = $this->db->query("SELECT * FROM EstadoAnimal ORDER BY id_estado");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getGaleria(int $id_animal): array {
        $stmt = $this->db->prepare(
            "SELECT id_foto, ruta, es_principal FROM Galeria
             WHERE id_animal = ? ORDER BY es_principal DESC, id_foto ASC"
        );
        $stmt->bind_param("i", $id_animal);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFoto(int $id_foto, int $id_animal): ?array {
        $stmt = $this->db->prepare(
            "SELECT ruta, es_principal FROM Galeria WHERE id_foto = ? AND id_animal = ?"
        );
        $stmt->bind_param("ii", $id_foto, $id_animal);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function addFoto(int $id_animal, string $ruta, int $es_principal): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO Galeria (id_animal, ruta, es_principal) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("isi", $id_animal, $ruta, $es_principal);
        return $stmt->execute();
    }

    public function deleteFoto(int $id_foto): bool {
        $stmt = $this->db->prepare("DELETE FROM Galeria WHERE id_foto = ?");
        $stmt->bind_param("i", $id_foto);
        return $stmt->execute();
    }

    public function setPrincipal(int $id_foto, int $id_animal): void {
        $upd = $this->db->prepare("UPDATE Galeria SET es_principal = 0 WHERE id_animal = ?");
        $upd->bind_param("i", $id_animal);
        $upd->execute();
        $upd2 = $this->db->prepare("UPDATE Galeria SET es_principal = 1 WHERE id_foto = ? AND id_animal = ?");
        $upd2->bind_param("ii", $id_foto, $id_animal);
        $upd2->execute();
    }

    public function getNextFoto(int $id_animal): ?array {
        $stmt = $this->db->prepare("SELECT id_foto FROM Galeria WHERE id_animal = ? LIMIT 1");
        $stmt->bind_param("i", $id_animal);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getFilterOptions(): array {
        $especies = []; $ciudades = []; $razas = []; $colores = [];
        $r = $this->db->query("SELECT DISTINCT especie FROM Animales WHERE especie IS NOT NULL ORDER BY especie");
        if ($r) while ($row = $r->fetch_assoc()) $especies[] = $row['especie'];
        $r = $this->db->query("SELECT DISTINCT ciudad FROM Protectora WHERE ciudad IS NOT NULL ORDER BY ciudad");
        if ($r) while ($row = $r->fetch_assoc()) $ciudades[] = $row['ciudad'];
        $r = $this->db->query("SELECT DISTINCT raza FROM Animales WHERE raza IS NOT NULL AND raza != '' ORDER BY raza");
        if ($r) while ($row = $r->fetch_assoc()) $razas[] = $row['raza'];
        $r = $this->db->query("SELECT DISTINCT color FROM Animales WHERE color IS NOT NULL AND color != '' ORDER BY color");
        if ($r) while ($row = $r->fetch_assoc()) $colores[] = $row['color'];
        return compact('especies', 'ciudades', 'razas', 'colores');
    }

    public function getFilterOptionsByProtectora(int $id_protectora): array {
        $especies = []; $razas = []; $colores = [];
        $r = $this->db->prepare("SELECT DISTINCT especie FROM Animales WHERE id_protectora = ? AND especie IS NOT NULL ORDER BY especie");
        $r->bind_param('i', $id_protectora); $r->execute();
        $res = $r->get_result(); while ($row = $res->fetch_assoc()) $especies[] = $row['especie'];
        $r = $this->db->prepare("SELECT DISTINCT raza FROM Animales WHERE id_protectora = ? AND raza IS NOT NULL AND raza != '' ORDER BY raza");
        $r->bind_param('i', $id_protectora); $r->execute();
        $res = $r->get_result(); while ($row = $res->fetch_assoc()) $razas[] = $row['raza'];
        $r = $this->db->prepare("SELECT DISTINCT color FROM Animales WHERE id_protectora = ? AND color IS NOT NULL AND color != '' ORDER BY color");
        $r->bind_param('i', $id_protectora); $r->execute();
        $res = $r->get_result(); while ($row = $res->fetch_assoc()) $colores[] = $row['color'];
        return compact('especies', 'razas', 'colores');
    }
}
