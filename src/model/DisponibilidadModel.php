<?php
class DisponibilidadModel {
    private mysqli $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getSlotsByMes(int $id_protectora, int $anyo, int $mes, int $dias_en_mes): array {
        $fecha_ini = "$anyo-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-01";
        $fecha_fin = "$anyo-" . str_pad($mes, 2, '0', STR_PAD_LEFT) . "-$dias_en_mes";
        $st = $this->db->prepare(
            "SELECT d.*, c.id_cita, c.estado AS estado_cita,
                    s.nombre AS nombre_adoptante, s.apellido,
                    a.nombre AS nombre_animal
             FROM DisponibilidadProtectora d
             LEFT JOIN CitaEntrevista c
                   ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
             LEFT JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
             LEFT JOIN Animales a ON s.id_animal = a.id_animal
             WHERE d.id_protectora = ? AND d.fecha BETWEEN ? AND ?
             ORDER BY d.fecha, d.hora_inicio"
        );
        $st->bind_param("iss", $id_protectora, $fecha_ini, $fecha_fin);
        $st->execute();
        $res = $st->get_result();
        $slots = [];
        while ($row = $res->fetch_assoc()) $slots[$row['fecha']][] = $row;
        return $slots;
    }

    public function addSlot(int $id_protectora, string $fecha, string $hora_ini, string $hora_fin): bool {
        $ins = $this->db->prepare(
            "INSERT IGNORE INTO DisponibilidadProtectora
                (id_protectora, fecha, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("isss", $id_protectora, $fecha, $hora_ini, $hora_fin);
        $ins->execute();
        return $this->db->affected_rows > 0;
    }

    public function addBloque(int $id_protectora, string $fecha, string $desde, string $hasta, int $duracion): int {
        $t_ini = strtotime("$fecha $desde");
        $t_fin = strtotime("$fecha $hasta");
        $ins = $this->db->prepare(
            "INSERT IGNORE INTO DisponibilidadProtectora
                (id_protectora, fecha, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)"
        );
        $added = 0;
        while ($t_ini + $duracion * 60 <= $t_fin) {
            $hi = date('H:i:s', $t_ini);
            $hf = date('H:i:s', $t_ini + $duracion * 60);
            $ins->bind_param("isss", $id_protectora, $fecha, $hi, $hf);
            $ins->execute();
            if ($this->db->affected_rows > 0) $added++;
            $t_ini += $duracion * 60;
        }
        return $added;
    }

    public function deleteSlot(int $id_slot, int $id_protectora): bool {
        $del = $this->db->prepare(
            "DELETE d FROM DisponibilidadProtectora d
             LEFT JOIN CitaEntrevista c
                   ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
             WHERE d.id_disponibilidad = ? AND d.id_protectora = ? AND c.id_cita IS NULL"
        );
        $del->bind_param("ii", $id_slot, $id_protectora);
        $del->execute();
        return $this->db->affected_rows > 0;
    }
}
