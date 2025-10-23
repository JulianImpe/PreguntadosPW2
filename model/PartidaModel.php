<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaYSuRespuesta()
    {
        // Obtenemos UNA pregunta aleatoria SOLO de la categoría "Medalla Cascada"
        $preguntaQuery = "SELECT p.ID, p.Texto 
                          FROM Pregunta p
                          INNER JOIN Medallas m ON p.Medalla_ID = m.ID
                          WHERE p.Estado_ID = 2 
                          AND m.Nombre = 'Medalla Cascada'
                          ORDER BY RAND() 
                          LIMIT 1";

        $pregunta = $this->database->query($preguntaQuery);

        // Si no hay preguntas aprobadas de esa categoría
        if (empty($pregunta)) {
            return []; // Devolver array vacío
        }

        $preguntaID = $pregunta[0]['ID'];

        // Luego traemos TODAS sus respuestas
        $respuestasQuery = "SELECT 
                                p.ID as preguntaID, 
                                p.Texto as preguntaTexto, 
                                r.ID as respuestaID, 
                                r.Texto as respuestaTexto, 
                                r.Es_Correcta as esCorrecta
                            FROM Pregunta p
                            INNER JOIN Respuesta r ON p.ID = r.Pregunta_ID
                            WHERE p.ID = $preguntaID
                            ORDER BY RAND()";

        return $this->database->query($respuestasQuery);
    }

    public function getRespuestaCorrecta($idPregunta)
    {
        $idPregunta = (int)$idPregunta;
        $query = "SELECT ID, Texto FROM Respuesta WHERE Pregunta_ID = $idPregunta AND Es_Correcta = 1";
        return $this->database->query($query);
    }

    public function getPreguntaPorId($preguntaId)
    {
        $preguntaId = (int)$preguntaId;
        $query = "SELECT p.ID, p.Texto 
              FROM Pregunta p 
              WHERE p.ID = $preguntaId";
        $result = $this->database->query($query);
        return !empty($result) ? $result[0] : null;
    }
}