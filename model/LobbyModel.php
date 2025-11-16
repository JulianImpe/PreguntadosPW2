<?php

class LobbyModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerDatosUsuario($usuarioId)
    {
        $usuarioId = (int)$usuarioId;

        $usuario = $this->database->query("
            SELECT 
                u.usuario,
                u.foto_perfil,
                u.Puntaje_total as puntaje
            FROM usuarios u
            WHERE u.ID = $usuarioId
        ");

        if (empty($usuario)) {
            return [];
        }

        $datos = $usuario[0];

        $partidas = $this->database->query("
            SELECT 
                COUNT(DISTINCT p.ID) as partidas_jugadas,
                SUM(CASE WHEN p.Estado_ID = 2 AND p.Puntaje_obtenido > 0 THEN 1 ELSE 0 END) as partidas_ganadas,
                SUM(CASE WHEN pp.EsCorrecta = 1 THEN 1 ELSE 0 END) as respuestas_correctas,
                COUNT(pp.ID) as respuestas_totales
            FROM Partida p
            LEFT JOIN Pregunta_partida pp ON p.ID = pp.Partida_ID
            WHERE p.Usuario_ID = $usuarioId
        ");

        $datos['partidas_jugadas'] = $partidas[0]['partidas_jugadas'] ?? 0;
        $datos['partidas_ganadas'] = $partidas[0]['partidas_ganadas'] ?? 0;

        $correctas = (int)($partidas[0]['respuestas_correctas'] ?? 0);
        $totales = (int)($partidas[0]['respuestas_totales'] ?? 1);
        
        if ($totales > 0) {
            $ratio = $correctas / $totales;
            $datos['nivel'] = max(1, min(10, ceil($ratio * 10)));
        } else {
            $datos['nivel'] = 1;
        }

        $ranking = $this->database->query("
            SELECT COUNT(*) + 1 as posicion
            FROM usuarios
            WHERE Puntaje_total > (SELECT Puntaje_total FROM usuarios WHERE ID = $usuarioId)
        ");

        $datos['ranking'] = $ranking[0]['posicion'] ?? '-';

        return $datos;
    }

    public function obtenerPartidasRecientes($usuarioId, $limite = 5)
    {
        $usuarioId = (int)$usuarioId;
        $limite = (int)$limite;

        $partidas = $this->database->query("
            SELECT 
                p.ID,
                p.Puntaje_obtenido as puntos_obtenidos,
                p.Hora_inicio as fecha,
                p.Estado_ID,
                COUNT(pp.ID) as totales,
                SUM(CASE WHEN pp.EsCorrecta = 1 THEN 1 ELSE 0 END) as correctas
            FROM Partida p
            LEFT JOIN Pregunta_partida pp ON p.ID = pp.Partida_ID
            WHERE p.Usuario_ID = $usuarioId AND p.Estado_ID = 2
            GROUP BY p.ID
            ORDER BY p.Hora_inicio DESC
            LIMIT $limite
        ");

        $resultado = [];
        foreach ($partidas as $p) {
            $resultado[] = [
                'fecha' => date('d/m/Y H:i', strtotime($p['fecha'])),
                'puntos_obtenidos' => $p['puntos_obtenidos'],
                'correctas' => $p['correctas'] ?? 0,
                'totales' => $p['totales'] ?? 0,
                'ganada' => $p['puntos_obtenidos'] > 0
            ];
        }

        return $resultado;
    }
}