<?php

class RegistrarseModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }
    public function getUserWith($user, $password)
    {
        $sql = "SELECT * FROM usuarios WHERE usuario = '$user' AND password = '$password'";
        $result = $this->conexion->query($sql);
        return $result ?? [];
    }

public function registrarUsuario($usuario, $password, $email, $fecha_nac, $foto_perfil, $nombre_completo, $sexo_id)
{
    $sql = "INSERT INTO usuarios (usuario, password, email, fecha_nac, foto_perfil, nombre_completo, Sexo_ID) 
            VALUES ('$usuario', '$password', '$email', '$fecha_nac', '$foto_perfil', '$nombre_completo', '$sexo_id')";
    $this->conexion->query($sql);
}

public function getSexoIdByNombre($nombre)
{
    $sql = "SELECT ID FROM Sexo WHERE Nombre = '$nombre'";
    $result = $this->conexion->query($sql);
    if (!empty($result)) {
        return $result[0]['ID'];
    }
    return null;
}


    public function existeUsuario($usuario)
{
    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
    $res = $this->conexion->query($sql);
    return !empty($res);
}

public function existeEmail($email)
{
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $res = $this->conexion->query($sql);
    return !empty($res);
}


}