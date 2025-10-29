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
        $queryPregunta = "
            SELECT p.ID, p.Texto 
            FROM Pregunta p
            INNER JOIN Medallas m ON p.Medalla_ID = m.ID
            WHERE p.Estado_ID = 2 
              AND m.Nombre = 'Medalla Cascada'
            ORDER BY RAND() 
            LIMIT 1";

        $pregunta = $this->database->query($queryPregunta);
        if (empty($pregunta)) return [];

        $preguntaID = $pregunta[0]['ID'];

        $queryRespuestas = "
            SELECT 
                p.ID AS preguntaID,
                p.Texto AS preguntaTexto,
                r.ID AS respuestaID,
                r.Texto AS respuestaTexto,
                r.Es_Correcta AS esCorrecta
            FROM Pregunta p
            INNER JOIN Respuesta r ON p.ID = r.Pregunta_ID
            WHERE p.ID = $preguntaID
            ORDER BY RAND()";

        return $this->database->query($queryRespuestas);
    }

    public function getRespuestaCorrecta($preguntaId)
    {
        $preguntaId = (int)$preguntaId;
        $query = "SELECT ID FROM Respuesta WHERE Pregunta_ID = $preguntaId AND Es_Correcta = 1";
        return $this->database->query($query);
    }

    public function getPreguntaPorId($preguntaId)
    {
        $preguntaId = (int)$preguntaId;
        $query = "SELECT ID, Texto FROM Pregunta WHERE ID = $preguntaId";
        $result = $this->database->query($query);
        return $result[0] ?? null;
    }


    public function getPreguntaRender()
    {
        $resultado = $this->getPreguntaYSuRespuesta();
        if (empty($resultado)) return null;

        // Encuentra la correcta
        foreach ($resultado as $r) {
            if ($r['esCorrecta'] == 1) {
                $respuestaCorrecta = $r['respuestaID'];
                break;
            }
        }

        return [
            "texto" => $resultado[0]['preguntaTexto'],
            "id" => $resultado[0]['preguntaID'],
            "respuesta_correcta_id" => $respuestaCorrecta,
            "respuestas" => array_map(function ($r, $i) {
                $esCorrecta = ($r['esCorrecta'] == 1);
                return [
                    "id" => $r['respuestaID'],
                    "texto" => $r['respuestaTexto'],
                    "letra" => chr(65 + $i),
                    "es_correcta" => $esCorrecta,
                    "es_correcta_str" => $esCorrecta ? '1' : '0'
                ];
            }, $resultado, array_keys($resultado))
        ];
    }

    public function procesarRespuesta($preguntaId, $respuestaId)
    {
        $respuestaCorrecta = $this->getRespuestaCorrecta($preguntaId);
        $esCorrecta = !empty($respuestaCorrecta) && $respuestaId == $respuestaCorrecta[0]['ID'];

        if ($esCorrecta) {
            $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 10;
        }

        return [
            'esCorrecta' => $esCorrecta,
            'puntaje' => $_SESSION['puntaje'] ?? 0,
            'pregunta' => $this->getPreguntaPorId($preguntaId),
            'partida_terminada' => !$esCorrecta
        ];
    }



    /*public function iniciarPartida($usuarioId)
    {
        $this->database->insert("INSERT INTO Partida (Usuario_ID) VALUES ($usuarioId)");
        return $this->database->lastInsertId();
    }*/


    public function registrarRespuesta($partidaId, $preguntaId, $esCorrecta)
    {
        $orden = $this->database->query("SELECT COUNT(*) AS n FROM Pregunta_partida WHERE Partida_ID = $partidaId")[0]["n"] + 1;

        $this->database->insert("
        INSERT INTO Pregunta_partida (Partida_ID, Pregunta_ID, Orden_en_partida, EsCorrecta)
        VALUES ($partidaId, $preguntaId, $orden, $esCorrecta)
    ");
    }

    public function finalizarPartida($partidaId, $puntaje)
    {
        $partidaId = (int)$partidaId;
        $puntaje = (int)$puntaje;
        $sql = "UPDATE Partida 
            SET Puntaje_obtenido = $puntaje, Estado_ID = 2, Hora_finalizacion = NOW() 
            WHERE ID = $partidaId";
        $this->database->query($sql); // acá no usamos update(), usamos query()
    }


    public function crearPartida($usuarioId)
    {
        $usuarioId = (int)$usuarioId;


        $usuario = $this->database->query("SELECT ID FROM usuarios WHERE ID = $usuarioId");
        if (empty($usuario)) {
            throw new Exception("No se puede crear la partida: usuario inexistente.");
        }

        $query = "INSERT INTO Partida (Usuario_ID) VALUES ($usuarioId)";
        $this->database->query($query);

        return $this->database->lastInsertId();
    }





}
