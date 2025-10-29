<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

public function getPreguntaYRespuesta()
{
    // Buscar una pregunta aleatoria de la medalla "Cascada"
    $sqlPregunta = "
        SELECT p.ID, p.Texto, p.Dificultad, p.DificultadNivel
        FROM Pregunta p
        INNER JOIN Medallas m ON p.Medalla_ID = m.ID
        WHERE p.Estado_ID = 2 
        AND m.Nombre = 'Medalla Cascada'
        ORDER BY RAND()
        LIMIT 1
    ";

    $pregunta = $this->database->query($sqlPregunta);
    if (empty($pregunta)) return [];

    $preguntaId = $pregunta[0]['ID'];

    // Traer las respuestas de esa pregunta
    $sqlRespuestas = "
        SELECT 
            p.ID AS preguntaId,
            p.Texto AS preguntaTexto,
            p.Dificultad,
            p.DificultadNivel,
            r.ID AS respuestaId,
            r.Texto AS respuestaTexto,
            r.Es_Correcta AS esCorrecta
        FROM Pregunta p
        INNER JOIN Respuesta r ON p.ID = r.Pregunta_ID
        WHERE p.ID = $preguntaId
        ORDER BY RAND()
    ";

    return $this->database->query($sqlRespuestas);
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


public function getPreguntaRender($targetDifficulty = 0.5)
{
    $preguntaYRespuestas = $this->getPreguntaYRespuesta();
    if (empty($preguntaYRespuestas)) return null;

    // Buscar la respuesta correcta
    $idRespuestaCorrecta = null;
    foreach ($preguntaYRespuestas as $respuesta) {
        if ($respuesta['esCorrecta'] == 1) {
            $idRespuestaCorrecta = $respuesta['respuestaId'];
            break;
        }
    }

    // Tomar dificultad y nivel de la primera fila
    $dificultad = $preguntaYRespuestas[0]['Dificultad'] ?? null;
    $nivelDificultad = $preguntaYRespuestas[0]['DificultadNivel'] ?? null;
    $dificultadFormateada = is_null($dificultad) ? null : number_format((float)$dificultad, 3, '.', '');


    $respuestas = [];
    foreach ($preguntaYRespuestas as $indice => $respuesta) {
        $esCorrecta = ($respuesta['esCorrecta'] == 1);
        $respuestas[] = [
            "id" => $respuesta['respuestaId'],
            "texto" => $respuesta['respuestaTexto'],
            "letra" => chr(65 + $indice),
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

public function procesarRespuesta($preguntaId, $respuestaId)
{
    $preguntaId = (int)$preguntaId;
    $respuestaId = (int)$respuestaId;

    //  Verifico si la respuesta elegida es la correcta
    $respuestaCorrecta = $this->getRespuestaCorrecta($preguntaId);
    $esCorrecta = !empty($respuestaCorrecta) && $respuestaId == $respuestaCorrecta[0]['ID'];

    //  Actualizo los contadores en la base de datos
    //    Si la respuesta fue correcta, sumo 1 también en Cant_veces_correcta
    $sumarCorrecta = $esCorrecta ? 1 : 0;
    $this->database->query("
        UPDATE Pregunta
        SET Cant_veces_respondida = Cant_veces_respondida + 1,
            Cant_veces_correcta = Cant_veces_correcta + $sumarCorrecta
        WHERE ID = $preguntaId
    ");

    // Me traigo los datos de la pregunta para calcular la dificultad
    $datosPregunta = $this->database->query("
        SELECT Cant_veces_respondida, Cant_veces_correcta
        FROM Pregunta
        WHERE ID = $preguntaId
    ");

    $cantidadRespondidas = (int)($datosPregunta[0]['Cant_veces_respondida'] ?? 0);
    $cantidadCorrectas = (int)($datosPregunta[0]['Cant_veces_correcta'] ?? 0);

    // aca calcula la nueva dificultad
    if ($cantidadRespondidas === 0) {
        $dificultad = 0; // No hay datos todavia
        $nivelDificultad = 'Nuevo';
    } else {
        //“De todas las veces que la pregunta fue respondida, 
        //¿en cuántas fue respondida correctamente?”
        //Si una pregunta se respondió 10 veces, y 7 fueron correctas:
        //7/10 = 0.7
        $probabilidadDeAcierto = $cantidadCorrectas / $cantidadRespondidas;

        // Dificultad = 1 - probabilidad de acierto
        //Saca el resto a 1, compara la probalidad de 7/10 por ejemplo que seria 0.7
        // y se lo resta a 1, quedaria --> 1 - 0.7 = 0.3
        $dificultad = 1 - $probabilidadDeAcierto;

        // Asigno un nivel según el valor numérico
        if ($dificultad <= 0.33) {
            $nivelDificultad = 'Fácil';
        } elseif ($dificultad <= 0.66) {
            $nivelDificultad = 'Medio';
        } else {
            $nivelDificultad = 'Difícil';
        }
    }

    // hace el update de la tabla pregunta
    $this->database->query("
        UPDATE Pregunta
        SET Dificultad = $dificultad,
            DificultadNivel = '{$nivelDificultad}'
        WHERE ID = $preguntaId
    ");

    // aca suma puntos en la sesion si es correcta
    if ($esCorrecta) {
        $_SESSION['puntaje'] = ($_SESSION['puntaje'] ?? 0) + 10;
    }

    // devuelve resultado
    return [
        'esCorrecta' => $esCorrecta,
        'puntaje' => $_SESSION['puntaje'] ?? 0,
        'pregunta' => $this->getPreguntaPorId($preguntaId)
    ];
}
public function traerPreguntasDificilesRandom()
{
    $query = "
        SELECT p.ID, p.Texto, COALESCE(p.Dificultad, 1) AS dificultad_score
        FROM Pregunta p
        WHERE p.Estado_ID = 2
        AND p.Medalla_ID = 2
        ORDER BY (RAND() * 0.4) + (dificultad_score * 0.6) DESC
        LIMIT 1";

    $resultado = $this->database->query($query);
    return $resultado;
}
}
