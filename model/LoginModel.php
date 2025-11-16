<?php

class LoginModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function getUserWith($usuario, $password)
    {
        if (empty($usuario) || empty($password)) {
            return [];
        }

        $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' LIMIT 1";
        $result = $this->conexion->query($sql);


        if (!empty($result)) {
            $user = $result[0];
            if ($user['password'] === $password) {
                return [$user];
            }
        }

        return [];
    }


}