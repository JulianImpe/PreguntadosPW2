<?php

class RuletaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getCategorias()
    {
        return $this->database->query("SELECT ID, Nombre, Imagen_url, Color FROM Medallas ORDER BY ID");
    }

    public function verificarPreguntasDisponibles($usuarioId, $medallaId, $partidaId)
    {
        $usuarioId = (int)$usuarioId;
        $medallaId = (int)$medallaId;
        $partidaId = (int)$partidaId;


        $nivelResult = $this->database->query("
            SELECT 
                SUM(CASE WHEN pp.EsCorrecta = 1 THEN 1 ELSE 0 END) as respuestas_correctas,
                COUNT(pp.ID) as respuestas_totales
            FROM Pregunta_partida pp
            INNER JOIN Partida p ON pp.Partida_ID = p.ID
            WHERE p.Usuario_ID = $usuarioId
        ");

        $nivelJugador = 0.5;
        if (!empty($nivelResult) && $nivelResult[0]['respuestas_totales'] > 0) {
            $correctas = (int)$nivelResult[0]['respuestas_correctas'];
            $totales = (int)$nivelResult[0]['respuestas_totales'];
            $nivelJugador = $correctas / $totales;
        }

        if ($nivelJugador <= 0.33) {
            $minDif = 0;
            $maxDif = 0.5;
        } elseif ($nivelJugador <= 0.66) {
            $minDif = 0.3;
            $maxDif = 0.7;
        } else {
            $minDif = 0.5;
            $maxDif = 1;
        }

        $resultado = $this->database->query("
            SELECT COUNT(*) as total
            FROM Pregunta p
            WHERE p.Estado_ID = 2
            AND p.Medalla_ID = $medallaId
            AND COALESCE(p.Dificultad, 0.5) BETWEEN $minDif AND $maxDif
            AND p.ID NOT IN (
                SELECT Pregunta_ID 
                FROM Pregunta_partida 
                WHERE Partida_ID = $partidaId
            )
        ");

        return isset($resultado[0]['total']) && $resultado[0]['total'] > 0;
    }

    public function getMedallaPorId($medallaId) {
        $medallaId = (int)$medallaId;
        $query = "SELECT ID as id, Nombre as nombre, Imagen_url as imagen_url, Color as color FROM Medallas WHERE ID = $medallaId LIMIT 1";
        $result = $this->database->query($query);
        return $result ? $result[0] : null;
    }
}