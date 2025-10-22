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
        return $this->database->query(
            'select * 
        from (SELECT *
        FROM pregunta
        ORDER BY RAND()
        LIMIT 1) a
        left join respuesta
        on a.id = respuesta.pregunta'
        );
    }

    public function getRespuestas()
    {
        return $this->database->query('SELECT * FROM respuesta');
    }


    public function getDescripcion($idRandom)// llmos el nobre de la imagen para guardar q categoria gano o perdio
    {
        $query = "SELECT descripcion FROM pregunta WHERE id like '%$idRandom%'";
        $result = $this->database->query($query);
        return $result[0]['descripcion'];
    }
    //public function reportar($descripcion, $id)
    //{
    // $query = "INSERT INTO preguntasreportadas(descripcion_reporte, pregunta_id) VALUES ('$descripcion', '$id')";
    // $this->database->queryB($query);
    //}

    public function getPreguntas()
    {
        return $this->database->query('SELECT * FROM pregunta');
    }

    public function getPreguntaPorID($idRandom)
    {
        //return $this->database->query('SELECT * FROM pregunta WHERE id like ' .  $idRandom);
        $query = 'SELECT p.id, p.descripcion, c.tipo, c.imagen
              FROM pregunta p
              JOIN categoria c ON p.categoria = c.id
              WHERE p.id = ' . $idRandom;

        return $this->database->query($query);
    }

    public function getRespuestaPorID($idRandom)
    {
        return $this->database->query('SELECT * FROM respuesta WHERE pregunta like  ' . $idRandom);
    }

    public function getRespuestaCorrecta($idRandom)
    {
        $idRandom = (int)$idRandom; // Asegurarse de que $idRandom sea un entero válido
        $query = 'SELECT CAST(es_correcta AS SIGNED) AS es_correcta_int FROM respuesta WHERE id = ' . $idRandom;
        return $this->database->query($query);
    }

    public function guardarPartida()
    {
        $usuario = $_SESSION['user'];
        $puntaje = $_SESSION['puntaje'];
        $this->database->queryB("INSERT INTO partida(user_name, puntaje, fecha) VALUES('$usuario', '$puntaje', NOW())");
    }

    public function getIdPartida($usuario)
    {
        $query = "SELECT MAX(id) AS max_id FROM partida WHERE user_name = '" . $usuario . "'";
        $result = $this->database->query($query);
        return $result;
    }

}