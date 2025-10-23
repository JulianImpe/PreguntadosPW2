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
        // Primero obtenemos UNA pregunta aleatoria
        $preguntaQuery = "SELECT ID, Texto 
                          FROM Pregunta 
                          WHERE Estado_ID = 2 
                          ORDER BY RAND() 
                          LIMIT 1";

        $pregunta = $this->database->query($preguntaQuery);

        // Si no hay preguntas aprobadas (tiene en cuenta el estados d el la preg con el id
        if (empty($pregunta)) {
            return []; // ← Devolver array vacío
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

    //  public function guardarPartida()
    // {
    //    $usuario = $_SESSION['usuario'] ?? 'invitado';
    //   $puntaje = $_SESSION['puntaje'] ?? 0;
    //   $this->database->query("INSERT INTO Partida (Creada_por_usuario_ID, puntaje, Fecha_creacion) VALUES ('$usuario', '$puntaje', NOW())");
    // }

    // public function getIdPartida($usuario)
    //  {
    //   $usuario = $this->database->escape($usuario);
    ///   $query = "SELECT MAX(ID) AS max_id FROM Partida WHERE Creada_por_usuario_ID = '$usuario'";
    //  $result = $this->database->query($query);
    //  return $result[0]['max_id'] ?? null;
    // }
}
