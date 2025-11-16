<?php

class SugerenciaModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function guardarSugerencia($usuarioId, $texto, $medallaId, $respuestas, $correcta)
    {
        $usuarioId = (int) $usuarioId;
        $medallaId = (int) $medallaId;

        $conexion = $this->database->getConexion();

        $texto = $conexion->real_escape_string($texto);


        $conexion->query("
            INSERT INTO Pregunta_sugerida (Texto, Medalla_ID, Sugerida_por_usuario_ID)
            VALUES ('$texto', $medallaId, $usuarioId)
        ");

        $preguntaId = $conexion->insert_id;


        for ($i = 0; $i < 4; $i++) {
            $respText = $conexion->real_escape_string($respuestas[$i]);
            $esCorrecta = ($correcta == $i) ? 1 : 0;

            $conexion->query("
                INSERT INTO Respuesta_sugerida (Pregunta_sugerida_ID, Texto, Es_Correcta)
                VALUES ($preguntaId, '$respText', $esCorrecta)
            ");
        }

        return [
            'ok' => true,
            'msg' => "✅ Tu pregunta fue enviada para revisión por un editor."
        ];
    }

    public function obtenerMedallas()
    {
        $conexion = $this->database->getConexion();
        $res = $conexion->query("SELECT ID, Nombre FROM Medallas ORDER BY ID ASC");

        $medallas = [];

        while ($fila = $res->fetch_assoc()) {
            $medallas[] = $fila;
        }

        return $medallas;
    }

}
