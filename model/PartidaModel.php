<?php

class PartidaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

public function getPreguntaYRespuesta($medallaId = null)
{
    // filtro por medalla si se pasa una
    $sqlMedalla = $medallaId ? "AND p.Medalla_ID = " . intval($medallaId) : "";

    // primero seleccionamos pregunta random
    $pregunta = $this->database->query("
        SELECT 
            p.ID AS preguntaId,
            p.Texto AS preguntaTexto,
            p.Dificultad,
            p.DificultadNivel
        FROM Pregunta p
        WHERE p.Estado_ID = 2
        $sqlMedalla
        ORDER BY RAND()
        LIMIT 1
    ");

    if (empty($pregunta)) return [];

    $preguntaId = $pregunta[0]['preguntaId'];

    // traemos las respuestas de la preguntas a la que pasamos con el id
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

// 3️⃣ Combinar la pregunta con las respuestas (una sola estructura)
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
    if (empty($preguntaYRespuestas)) {
    var_dump("No hay preguntas en la BD"); die;
}

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
$usadas = [];

foreach ($preguntaYRespuestas as $indice => $respuesta) {
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
public function traerPreguntasDificilesRandom($medallaId = null)
{
    //guardo el where en una variable
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

}
