<?php
class AdopcionModel {
    private $db;

    public function __construct(mysqli $db) { $this->db = $db; }

    public function getSolicitudesByProtectora(int $id_protectora, string $estado = 'PENDIENTE'): array {
        $sql = "SELECT s.*, a.nombre AS nombre_animal, a.especie, a.raza,
                       (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal
                        ORDER BY g.es_principal DESC LIMIT 1) AS foto_animal,
                       c.id_cita, c.estado AS estado_cita,
                       d.fecha AS fecha_cita, d.hora_inicio AS hora_cita
                FROM SolicitudAdopcion s
                JOIN Animales a ON s.id_animal = a.id_animal
                LEFT JOIN CitaEntrevista c ON c.id_solicitud = s.id_solicitud
                LEFT JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
                WHERE a.id_protectora = ?";
        if ($estado !== 'TODAS') $sql .= " AND s.estado_solicitud = ?";
        $sql .= " ORDER BY s.fecha_solicitud DESC";
        if ($estado !== 'TODAS') {
            $st = $this->db->prepare($sql);
            $st->bind_param("is", $id_protectora, $estado);
        } else {
            $st = $this->db->prepare($sql);
            $st->bind_param("i", $id_protectora);
        }
        $st->execute();
        return $st->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getContadoresByProtectora(int $id_protectora): array {
        $contadores = ['PENDIENTE' => 0, 'APROBADA' => 0, 'RECHAZADA' => 0];
        $st = $this->db->prepare(
            "SELECT s.estado_solicitud, COUNT(*) AS total
             FROM SolicitudAdopcion s JOIN Animales a ON s.id_animal = a.id_animal
             WHERE a.id_protectora = ?
             GROUP BY s.estado_solicitud"
        );
        $st->bind_param("i", $id_protectora);
        $st->execute();
        $r = $st->get_result();
        while ($row = $r->fetch_assoc()) $contadores[$row['estado_solicitud']] = (int)$row['total'];
        return $contadores;
    }

    public function updateEstado(int $id_solicitud, string $nuevo_estado, int $id_protectora): bool {
        $chk = $this->db->prepare(
            "SELECT s.id_solicitud FROM SolicitudAdopcion s
             JOIN Animales a ON s.id_animal = a.id_animal
             WHERE s.id_solicitud = ? AND a.id_protectora = ?"
        );
        $chk->bind_param("ii", $id_solicitud, $id_protectora);
        $chk->execute();
        if ($chk->get_result()->num_rows === 0) return false;
        $upd = $this->db->prepare("UPDATE SolicitudAdopcion SET estado_solicitud = ? WHERE id_solicitud = ?");
        $upd->bind_param("si", $nuevo_estado, $id_solicitud);
        return $upd->execute();
    }

    public function countPendientesByProtectora(int $id_protectora): int {
        $st = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM SolicitudAdopcion s
             JOIN Animales a ON s.id_animal = a.id_animal
             WHERE a.id_protectora = ? AND s.estado_solicitud = 'PENDIENTE'"
        );
        $st->bind_param("i", $id_protectora);
        $st->execute();
        return (int)($st->get_result()->fetch_assoc()['n'] ?? 0);
    }

    public function getSolicitudesByAdoptante(int $id_adoptante): array {
        $stmt = $this->db->prepare(
            "SELECT s.id_solicitud, s.id_animal, s.estado_solicitud, s.fecha_solicitud,
                    a.nombre AS nombre_animal, a.especie,
                    p.nombre_protectora,
                    c.id_cita, d.fecha AS fecha_cita, d.hora_inicio, d.hora_fin,
                    c.estado AS estado_cita
             FROM SolicitudAdopcion s
             JOIN Animales a ON s.id_animal = a.id_animal
             JOIN Protectora p ON a.id_protectora = p.id_protectora
             LEFT JOIN CitaEntrevista c ON c.id_solicitud = s.id_solicitud AND c.estado != 'CANCELADA'
             LEFT JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
             WHERE s.id_adoptante = ?
             ORDER BY s.fecha_solicitud DESC"
        );
        $stmt->bind_param("i", $id_adoptante);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function existeSolicitudPendiente(int $id_animal, int $id_adoptante): bool {
        $stmt = $this->db->prepare(
            "SELECT id_solicitud FROM SolicitudAdopcion
             WHERE id_animal = ? AND id_adoptante = ? AND estado_solicitud = 'PENDIENTE'"
        );
        $stmt->bind_param("ii", $id_animal, $id_adoptante);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function createSolicitud(array $d) {
        $stmt = $this->db->prepare(
            "INSERT INTO SolicitudAdopcion
                (id_animal, id_adoptante, nombre, apellido, email, telefono, dni,
                 tipo_vivienda, tiene_jardin, metros_vivienda, tiene_ninos, edades_ninos,
                 tiene_animales, desc_animales, horas_solo, experiencia, motivacion,
                 acepta_visita, acepta_seguimiento)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            "iissssssisisisiisii",
            $d['id_animal'], $d['id_adoptante'], $d['nombre'], $d['apellido'], $d['email'],
            $d['telefono'], $d['dni'], $d['tipo_vivienda'], $d['tiene_jardin'], $d['metros_vivienda'],
            $d['tiene_ninos'], $d['edades_ninos'], $d['tiene_animales'], $d['desc_animales'],
            $d['horas_solo'], $d['experiencia'], $d['motivacion'],
            $d['acepta_visita'], $d['acepta_seguimiento']
        );
        return $stmt->execute() ? $this->db->insert_id : false;
    }

    public function getSolicitudConAnimal(int $id_solicitud, int $id_adoptante): ?array {
        $st = $this->db->prepare(
            "SELECT s.*, a.nombre AS nombre_animal, a.especie, a.id_protectora,
                    p.nombre_protectora, p.ciudad,
                    (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal
                     ORDER BY g.es_principal DESC LIMIT 1) AS foto_animal
             FROM SolicitudAdopcion s
             JOIN Animales a ON s.id_animal = a.id_animal
             JOIN Protectora p ON a.id_protectora = p.id_protectora
             WHERE s.id_solicitud = ? AND s.id_adoptante = ?"
        );
        $st->bind_param("ii", $id_solicitud, $id_adoptante);
        $st->execute();
        return $st->get_result()->fetch_assoc() ?: null;
    }

    public function getCitaExistente(int $id_solicitud): ?array {
        $stmt = $this->db->prepare(
            "SELECT c.id_cita, d.fecha, d.hora_inicio, d.hora_fin
             FROM CitaEntrevista c
             JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
             WHERE c.id_solicitud = ? AND c.estado != 'CANCELADA'"
        );
        $stmt->bind_param("i", $id_solicitud);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function getSlotsByProtectora(int $id_protectora): array {
        $stmt = $this->db->prepare(
            "SELECT d.*
             FROM DisponibilidadProtectora d
             LEFT JOIN CitaEntrevista c
                   ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
             WHERE d.id_protectora = ? AND d.disponible = 1
               AND d.fecha >= CURDATE() AND c.id_cita IS NULL
             ORDER BY d.fecha, d.hora_inicio"
        );
        $stmt->bind_param("i", $id_protectora);
        $stmt->execute();
        $r = $stmt->get_result();
        $slots = [];
        while ($row = $r->fetch_assoc()) $slots[$row['fecha']][] = $row;
        return $slots;
    }

    public function verificarSlotDisponible(int $id_slot, int $id_protectora): bool {
        $stmt = $this->db->prepare(
            "SELECT d.id_disponibilidad
             FROM DisponibilidadProtectora d
             LEFT JOIN CitaEntrevista c
                   ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
             WHERE d.id_disponibilidad = ? AND d.id_protectora = ?
               AND d.disponible = 1 AND d.fecha >= CURDATE() AND c.id_cita IS NULL"
        );
        $stmt->bind_param("ii", $id_slot, $id_protectora);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function reservarCita(int $id_solicitud, int $id_disponibilidad): bool {
        $ins = $this->db->prepare(
            "INSERT INTO CitaEntrevista (id_solicitud, id_disponibilidad) VALUES (?, ?)"
        );
        $ins->bind_param("ii", $id_solicitud, $id_disponibilidad);
        return $ins->execute();
    }

    public function cancelarCitaAdoptante(int $id_cita, int $id_adoptante): bool {
        $stmt = $this->db->prepare(
            "UPDATE CitaEntrevista c
             JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
             SET c.estado = 'CANCELADA'
             WHERE c.id_cita = ? AND s.id_adoptante = ?"
        );
        $stmt->bind_param("ii", $id_cita, $id_adoptante);
        return $stmt->execute();
    }

    public function cancelarCitaProtectora(int $id_cita, int $id_protectora): bool {
        $stmt = $this->db->prepare(
            "UPDATE CitaEntrevista c
             JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
             SET c.estado = 'CANCELADA'
             WHERE c.id_cita = ? AND d.id_protectora = ?"
        );
        $stmt->bind_param("ii", $id_cita, $id_protectora);
        return $stmt->execute();
    }

    public function cancelarCitaPorSolicitud(int $id_cita, int $id_solicitud): bool {
        $stmt = $this->db->prepare(
            "UPDATE CitaEntrevista SET estado = 'CANCELADA' WHERE id_cita = ? AND id_solicitud = ?"
        );
        $stmt->bind_param("ii", $id_cita, $id_solicitud);
        return $stmt->execute();
    }

    public function getProximasCitasByProtectora(int $id_protectora, int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT d.fecha, d.hora_inicio, a.nombre AS nombre_animal,
                    s.nombre AS nombre_adoptante, s.apellido
             FROM CitaEntrevista c
             JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
             JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
             JOIN Animales a ON s.id_animal = a.id_animal
             WHERE d.id_protectora = ? AND d.fecha >= CURDATE() AND c.estado != 'CANCELADA'
             ORDER BY d.fecha, d.hora_inicio LIMIT $limit"
        );
        $stmt->bind_param("i", $id_protectora);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProximasCitasPerfilProtectora(int $id_protectora, int $limit = 8): array {
        $stmt = $this->db->prepare(
            "SELECT c.id_cita, d.fecha, d.hora_inicio, d.hora_fin,
                    a.nombre AS nombre_animal,
                    s.nombre AS nombre_adoptante, s.apellido, s.telefono,
                    c.estado AS estado_cita
             FROM CitaEntrevista c
             JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
             JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
             JOIN Animales a ON s.id_animal = a.id_animal
             WHERE d.id_protectora = ? AND d.fecha >= CURDATE() AND c.estado != 'CANCELADA'
             ORDER BY d.fecha, d.hora_inicio LIMIT $limit"
        );
        $stmt->bind_param("i", $id_protectora);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
