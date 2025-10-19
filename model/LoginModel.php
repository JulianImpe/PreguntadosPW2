<?php

class LoginModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

  /*  public function getUserWith($usuario, $password)
    {
        $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND password = '$password'";
        $result = $this->conexion->query($sql);
        return $result ?? [];
    }*/
    public function getUserWith($usuario, $password)
    {
        if (empty($usuario) || empty($password)) {
            return [];
        }

        $sql = "SELECT * FROM usuarios WHERE usuario = ? LIMIT 1";
        $result = $this->conexion->query($sql, [$usuario]);

        if (!empty($result)) {
            $user = $result[0];
            // Si guardas contraseñas hasheadas:
            // if (password_verify($password, $user['password'])) {
            //     return [$user];
            // }

            // Si guardas contraseñas en texto plano (no recomendado):
            if ($user['password'] === $password) {
                return [$user];
            }
        }

        return [];
    }


}