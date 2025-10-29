<?php

class MyConexion
{

    private $conexion;

    public function __construct($server, $user, $pass, $database)
    {
        $this->conexion = new mysqli($server, $user, $pass, $database);
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


    /*public function insert($sql)
    {
        $result = $this->conexion->query($sql);
        if (!$result) {
            die("Error en INSERT: " . $this->conexion->error . " | SQL: " . $sql);
        }
        return $this->conexion->insert_id;
    }

    // MÉTODO ADICIONAL: Para updates explícitos
    public function update($sql)
    {
        $result = $this->conexion->query($sql);
        if (!$result) {
            die("Error en UPDATE: " . $this->conexion->error . " | SQL: " . $sql);
        }
        return $this->conexion->affected_rows;
    }*/
}
