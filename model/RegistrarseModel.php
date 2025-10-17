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

    public function registrarUsuario($usuario, $password, $email, $fecha_nac, $foto_perfil, $nombre_completo)
    {
        // Corregir nombres de columnas y valores
        $sql = "INSERT INTO usuarios (usuario, password, email, fecha_nac, foto_perfil, nombre_completo) 
                VALUES ('$usuario', '$password', '$email', '$fecha_nac', '$foto_perfil', '$nombre_completo')";
        $this->conexion->query($sql);
    }
}