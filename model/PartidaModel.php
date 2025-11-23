<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function getPreguntaYRespuesta($usuarioId, $medallaId = null)
    {
        $usuarioId = (int)$usuarioId;

        $nivelJugador = $this->obtenerNivelJugador($usuarioId);

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

        $sqlMedalla = $medallaId ? "AND p.Medalla_ID = " . intval($medallaId) : "";

        $pregunta = $this->database->query("
            SELECT 
                p.ID AS preguntaId,
                p.Texto AS preguntaTexto,
                p.Dificultad,
                p.DificultadNivel
            FROM Pregunta p
            LEFT JOIN Usuario_pregunta_vista upv 
                ON p.ID = upv.Pregunta_ID AND upv.Usuario_ID = $usuarioId
            WHERE p.Estado_ID = 2
            AND upv.ID IS NULL
            AND COALESCE(p.Dificultad, 0.5) BETWEEN $minDif AND $maxDif
            $sqlMedalla
            ORDER BY RAND()
            LIMIT 1
        ");

        if (empty($pregunta)) {
            $this->database->query("DELETE FROM Usuario_pregunta_vista WHERE Usuario_ID = $usuarioId");

            $pregunta = $this->database->query("
                SELECT 
                    p.ID AS preguntaId,
                    p.Texto AS preguntaTexto,
                    p.Dificultad,
                    p.DificultadNivel
                FROM Pregunta p
                WHERE p.Estado_ID = 2
                AND COALESCE(p.Dificultad, 0.5) BETWEEN $minDif AND $maxDif
                $sqlMedalla
                ORDER BY RAND()
                LIMIT 1
            ");
        }

        if (empty($pregunta)) return [];

        $preguntaId = $pregunta[0]['preguntaId'];

        $this->marcarPreguntaVista($usuarioId, $preguntaId);

        $respuestas = $this->database->query("
            SELECT DISTINCT
                r.ID AS respuestaId,
                r.Texto AS respuestaTexto,
                r.Es_Correcta AS esCorrecta
            FROM Respuesta r
            WHERE r.Pregunta_ID = $preguntaId
            ORDER BY RAND()
            LIMIT 4
        ");

        $resultado = [];
        foreach ($respuestas as $r) {
            $resultado[] = [
                'preguntaId' => $preguntaId,
                'preguntaTexto' => $pregunta[0]['preguntaTexto'],
                'Dificultad' => $pregunta[0]['Dificultad'],
                'DificultadNivel' => $pregunta[0]['DificultadNivel'],
                'respuestaId' => $r['respuestaId'],
                'respuestaTexto' => $r['respuestaTexto'],
                'esCorrecta' => $r['esCorrecta']
            ];
        }

        return $resultado;
    }

    private function marcarPreguntaVista($usuarioId, $preguntaId)
    {
        $usuarioId = (int)$usuarioId;
        $preguntaId = (int)$preguntaId;

        $this->database->query("
            INSERT IGNORE INTO Usuario_pregunta_vista (Usuario_ID, Pregunta_ID)
            VALUES ($usuarioId, $preguntaId)
        ");
    }

    public function obtenerNivelJugador($usuarioId)
    {
        $usuarioId = (int)$usuarioId;

        $resultado = $this->database->query("
            SELECT 
                COUNT(DISTINCT pp.Partida_ID) as partidas_totales,
                SUM(CASE WHEN pp.EsCorrecta = 1 THEN 1 ELSE 0 END) as respuestas_correctas,
                COUNT(pp.ID) as respuestas_totales
            FROM Pregunta_partida pp
            INNER JOIN Partida p ON pp.Partida_ID = p.ID
            WHERE p.Usuario_ID = $usuarioId
        ");

        if (empty($resultado) || $resultado[0]['respuestas_totales'] == 0) {
            return 0.5;
        }

        $correctas = (int)$resultado[0]['respuestas_correctas'];
        $totales = (int)$resultado[0]['respuestas_totales'];

        return $correctas / $totales;
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

    public function getPreguntaRender($usuarioId)
    {
        $preguntaYRespuestas = $this->getPreguntaYRespuesta($usuarioId);

        if (empty($preguntaYRespuestas)) {
            return null;
        }

        $idRespuestaCorrecta = null;
        foreach ($preguntaYRespuestas as $respuesta) {
            if ($respuesta['esCorrecta'] == 1) {
                $idRespuestaCorrecta = $respuesta['respuestaId'];
                break;
            }
        }

        $dificultad = $preguntaYRespuestas[0]['Dificultad'] ?? 0.5;
        $nivelDificultad = $preguntaYRespuestas[0]['DificultadNivel'] ?? 'Medio';
        $dificultadFormateada = number_format((float)$dificultad, 3, '.', '');

        $respuestas = [];
        $usadas = [];

        foreach ($preguntaYRespuestas as $respuesta) {
            if (in_array($respuesta['respuestaId'], $usadas)) continue;
            $usadas[] = $respuesta['respuestaId'];

            $esCorrecta = ($respuesta['esCorrecta'] == 1);
            $respuestas[] = [
                "id" => $respuesta['respuestaId'],
                "texto" => $respuesta['respuestaTexto'],
                "letra" => chr(65 + count($respuestas)),
                "es_correcta" => $esCorrecta,
                "es_correcta_str" => $esCorrecta ? '1' : '0'
            ];
        }

        return [
            "id" => $preguntaYRespuestas[0]['preguntaId'],
            "texto" => $preguntaYRespuestas[0]['preguntaTexto'],
            "respuesta_correcta_id" => $idRespuestaCorrecta,
            "dificultad" => $dificultadFormateada,
            "nivel_dificultad" => $nivelDificultad,
            "respuestas" => $respuestas
        ];
    }

    public function procesarRespuesta($preguntaId, $respuestaId, $tiempoAgotado = false)
    {
        $preguntaId = (int)$preguntaId;
        $respuestaId = (int)$respuestaId;

        $respuestaCorrecta = $this->getRespuestaCorrecta($preguntaId);
        $esCorrecta = !empty($respuestaCorrecta) && $respuestaId == $respuestaCorrecta[0]['ID'];

        if ($tiempoAgotado) {
            $esCorrecta = false;
        }

        $datosPrevios = $this->database->query("
        SELECT COALESCE(Dificultad, 0.5) AS Dificultad
        FROM Pregunta
        WHERE ID = $preguntaId
    ");
        $dificultadAnterior = (float)($datosPrevios[0]['Dificultad'] ?? 0.5);

        $sumarCorrecta = $esCorrecta ? 1 : 0;

        $this->database->query("
        UPDATE Pregunta
        SET Cant_veces_respondida = Cant_veces_respondida + 1,
            Cant_veces_correcta = Cant_veces_correcta + $sumarCorrecta
        WHERE ID = $preguntaId
    ");

        $datosPregunta = $this->database->query("
        SELECT Cant_veces_respondida, Cant_veces_correcta
        FROM Pregunta
        WHERE ID = $preguntaId
    ");

        $cantidadRespondidas = (int)($datosPregunta[0]['Cant_veces_respondida'] ?? 0);
        $cantidadCorrectas = (int)($datosPregunta[0]['Cant_veces_correcta'] ?? 0);

        if ($cantidadRespondidas === 0) {
            $dificultad = 0.5;
            $nivelDificultad = 'Medio';
        } else {
            $probabilidadDeAcierto = $cantidadCorrectas / $cantidadRespondidas;
            $dificultad = 1 - $probabilidadDeAcierto;

            if ($dificultad <= 0.33) {
                $nivelDificultad = 'Fácil';
            } elseif ($dificultad <= 0.66) {
                $nivelDificultad = 'Medio';
            } else {
                $nivelDificultad = 'Difícil';
            }

            $this->database->query("
            UPDATE Pregunta
            SET Dificultad = $dificultad,
                DificultadNivel = '{$nivelDificultad}'
            WHERE ID = $preguntaId
        ");
        }

        if ($esCorrecta) {
            if ($dificultadAnterior <= 0.33) {
                $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 10;
            } elseif ($dificultadAnterior <= 0.66) {
                $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 20;
            } else {
                $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 30;
            }
        }

        return [
            'esCorrecta' => $esCorrecta,
            'puntaje' => $_SESSION['puntaje'] ?? 0,
            'pregunta' => $this->getPreguntaPorId($preguntaId),
            'partida_terminada' => !$esCorrecta || $tiempoAgotado
        ];
    }


    public function registrarRespuesta($partidaId, $preguntaId, $esCorrecta)
    {
        $partidaId = (int)$partidaId;
        $preguntaId = (int)$preguntaId;
        $esCorrecta = (int)$esCorrecta;

        $orden = $this->database->query("SELECT COUNT(*) AS n FROM Pregunta_partida WHERE Partida_ID = $partidaId")[0]["n"] + 1;

        $this->database->query("
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
        $this->database->query($sql);
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


    public function traerPreguntasDificilesRandom($medallaId = null)
    {
        $where = "p.Estado_ID = 2";

        if (!is_null($medallaId)) {
            $where .= " AND p.Medalla_ID = " . intval($medallaId);
        }

        $query = "
        SELECT 
            p.ID, 
            p.Texto, 
            COALESCE(p.Dificultad, 1) AS dificultad_score,
            p.Medalla_ID
        FROM Pregunta p
        WHERE $where
        ORDER BY (RAND() * 0.4) + (dificultad_score * 0.6) DESC
        LIMIT 1
    ";

        return $this->database->query($query);
    }

    public function enviarReporte($preguntaId, $usuarioId, $motivo)
    {
        if (!$preguntaId || !$usuarioId || empty(trim($motivo))) {
            return ['ok' => false, 'msg' => 'Debes escribir un motivo.', 'partida_perdida' => false];
        }

        $usuarioId = (int)$usuarioId;

        $partida = $this->database->query("
        SELECT ID 
        FROM Partida 
        WHERE Usuario_ID = $usuarioId 
          AND Estado_ID = 1 
        ORDER BY ID DESC 
        LIMIT 1
    ");

        if (empty($partida)) {
            return ['ok' => false, 'msg' => 'No tienes una partida activa.', 'partida_perdida' => false];
        }

        $partidaId = (int)$partida[0]['ID'];

        $r = $this->database->query("
        SELECT COUNT(*) AS total 
        FROM Reporte 
        WHERE Usuario_ID = $usuarioId 
          AND Partida_ID = $partidaId
    ");

        $totalReportes = (int)($r[0]['total'] ?? 0);

        if ($totalReportes >= 1) {
            $this->finalizarPartida($partidaId, $_SESSION['puntaje'] ?? 0);

            return [
                'ok' => true,
                'msg' => '',
                'partida_perdida' => true
            ];
        }

        $guardado = $this->guardarReporte($preguntaId, $usuarioId, $motivo, $partidaId);

        if ($guardado) {
            return [
                'ok' => true,
                'msg' => 'Reporte enviado. Ya no podes volver a reportar durante la partida.',
                'partida_perdida' => false
            ];
        } else {
            return [
                'ok' => false,
                'msg' => 'Error al enviar el reporte.',
                'partida_perdida' => false
            ];
        }
    }
    public function guardarReporte($preguntaId, $usuarioId, $motivo, $partidaId = null) {
        $conexion = $this->database->getConexion();
        $motivo = $conexion->real_escape_string($motivo);
        $partidaIdSQL = $partidaId ? (int)$partidaId : "NULL";

        $query = "
        INSERT INTO Reporte (Pregunta_ID, Usuario_ID, Partida_ID, Motivo, Estado, Fecha_reporte)
        VALUES ($preguntaId, $usuarioId, $partidaIdSQL, '$motivo', 'Pendiente', NOW())";

        return $conexion->query($query);
    }

    public function getMedallaDeLaPregunta($preguntaId) {
        $preguntaId = (int)$preguntaId;
        $query = "
        SELECT m.ID as id, m.Nombre as nombre, m.Imagen_url as imagen_url
        FROM Medallas m
        INNER JOIN Pregunta p On p.Medalla_ID = m.ID
        WHERE p.ID = $preguntaId
        LIMIT 1";
        $result = $this->database->query($query);
        return $result ? $result[0] : null;
    }

    
}
