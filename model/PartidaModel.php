<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    /**
     * Obtiene una pregunta que el usuario NO haya visto aún
     * Filtra por medalla si se especifica
     * Ajusta según el nivel del jugador
     */
    public function getPreguntaYRespuesta($usuarioId, $medallaId = null)
    {
        $usuarioId = (int)$usuarioId;
        
        // Obtener nivel del jugador
        $nivelJugador = $this->obtenerNivelJugador($usuarioId);
        
        // Definir rango de dificultad según nivel
        // Nivel bajo (0-0.33): preguntas fáciles (0-0.5)
        // Nivel medio (0.34-0.66): preguntas medias (0.3-0.7)
        // Nivel alto (0.67-1): preguntas difíciles (0.5-1)
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
        
        // Buscar pregunta que NO haya visto y esté en su rango de dificultad
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

        // Si no hay preguntas sin ver, hacer TRUNCATE y reiniciar
        if (empty($pregunta)) {
            $this->database->query("DELETE FROM Usuario_pregunta_vista WHERE Usuario_ID = $usuarioId");
            
            // Volver a buscar
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
        
        // Marcar pregunta como vista
        $this->marcarPreguntaVista($usuarioId, $preguntaId);

        // Traer respuestas
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

    /**
     * Marca una pregunta como vista por el usuario
     */
    private function marcarPreguntaVista($usuarioId, $preguntaId)
    {
        $usuarioId = (int)$usuarioId;
        $preguntaId = (int)$preguntaId;
        
        $this->database->query("
            INSERT IGNORE INTO Usuario_pregunta_vista (Usuario_ID, Pregunta_ID)
            VALUES ($usuarioId, $preguntaId)
        ");
    }

    /**
     * Calcula el nivel del jugador según su ratio de aciertos
     * Retorna un valor entre 0 y 1
     */
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
            return 0.5; // Nivel medio por defecto
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

    /**
     * Procesa la respuesta y actualiza dificultad
     * Ahora también considera si se acabó el tiempo
     */
    public function procesarRespuesta($preguntaId, $respuestaId, $tiempoAgotado = false)
    {
        $preguntaId = (int)$preguntaId;
        $respuestaId = (int)$respuestaId;

        $respuestaCorrecta = $this->getRespuestaCorrecta($preguntaId);
        $esCorrecta = !empty($respuestaCorrecta) && $respuestaId == $respuestaCorrecta[0]['ID'];
        
        // Si se agotó el tiempo, la respuesta es incorrecta
        if ($tiempoAgotado) {
            $esCorrecta = false;
        }
        
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

            // Sumar puntos solo si es correcta
            if ($esCorrecta) {
                if ($dificultad <= 0.33) {
                    $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 10;
                } elseif ($dificultad <= 0.66) {
                    $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 20;
                } else {
                    $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 30;
                }
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
}