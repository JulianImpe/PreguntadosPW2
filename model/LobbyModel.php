<?php

class LobbyModel {
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }



    public function obtenerDatosUsuario($usuario)
    {
        $sql = "SELECT 
                    u.usuario,
                    COALESCE(u.puntos, 0) as puntos,
                    COALESCE(COUNT(p.id), 0) as partidas_jugadas,
                    COALESCE(SUM(CASE WHEN p.ganada = 1 THEN 1 ELSE 0 END), 0) as partidas_ganadas,
                    COALESCE(u.nivel, 1) as nivel
                FROM usuarios u
                LEFT JOIN partidas p ON u.id = p.usuario_id
                WHERE u.usuario = ?
                GROUP BY u.id";

        $result = $this->conexion->query($sql, [$usuario]);

        if (!empty($result)) {
            $datos = $result[0];

            // Calcular ranking
            $datos['ranking'] = $this->calcularRanking($usuario);

            return $datos;
        }

        return [];
    }

    public function obtenerPartidasRecientes($usuario, $limite = 5)
    {
        $sql = "SELECT 
                    p.fecha,
                    p.puntos_obtenidos,
                    p.respuestas_correctas as correctas,
                    p.total_preguntas as totales,
                    p.ganada
                FROM partidas p
                INNER JOIN usuarios u ON p.usuario_id = u.id
                WHERE u.usuario = ?
                ORDER BY p.fecha DESC
                LIMIT ?";

        return $this->conexion->query($sql, [$usuario, $limite]);
    }

    private function calcularRanking($usuario)
    {
        $sql = "SELECT COUNT(*) + 1 as ranking
                FROM usuarios u1
                WHERE u1.puntos > (
                    SELECT puntos 
                    FROM usuarios 
                    WHERE usuario = ?
                )";

        $result = $this->conexion->query($sql, [$usuario]);

        return !empty($result) ? $result[0]['ranking'] : '-';
    }
}