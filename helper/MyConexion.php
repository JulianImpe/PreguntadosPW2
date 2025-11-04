<?php

class MyConexion
{

    private $conexion;

    public function __construct($server, $user, $pass, $database)
    {
        $this->conexion = new mysqli($server, $user, $pass, $database);
        $this->conexion->set_charset("utf8mb4");
        if ($this->conexion->error) {
            die("Error en la conexión: " . $this->conexion->error);
        }
    }

    public function query($sql)
    {
        $result = $this->conexion->query($sql);
        if (!$result) {
            die("Error en la consulta: " . $this->conexion->error);
        }
        if ($result instanceof mysqli_result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }


    public function lastInsertId()
    {
        return $this->conexion->insert_id;
    }



}
