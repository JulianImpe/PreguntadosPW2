<?php

class RankingModel{
private $conexion;

public function __construct($conexion){
    $this->conexion = $conexion;
}

public function obtenerRanking(){

    $sql = "SELECT
       u.ID as id,
    u.usuario, 
    MAX(p.Puntaje_obtenido) AS mejor_puntaje
        FROM Partida p
        INNER JOIN usuarios u ON p.Usuario_ID = u.ID
        GROUP BY u.ID, u.usuario
        ORDER BY mejor_puntaje DESC
        LIMIT 10";


    return $this->conexion->query($sql);
}
}