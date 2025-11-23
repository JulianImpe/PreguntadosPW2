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
        $sql = "SELECT * FROM usuarios 
                WHERE usuario = '$user' 
                AND password = '$password' 
                AND email_validado = 1";
        $result = $this->conexion->query($sql);
        return $result ?? [];
    }

    public function registrarUsuario($usuario, $password, $email, $fecha_nac, $foto_perfil, $nombre_completo, $sexo_id, $token, $pais, $ciudad)
    {
        $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $sql = "INSERT INTO usuarios (usuario, password, email, fecha_nac, foto_perfil, nombre_completo, Sexo_ID, token_validacion, token_expira, email_validado, pais, ciudad) 
                VALUES ('$usuario', '$password', '$email', '$fecha_nac', '$foto_perfil', '$nombre_completo', '$sexo_id', '$token', '$expira', 0, '$pais', '$ciudad')";

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

    public function validarToken($email, $token)
    {
        $email = trim($email);
        $token = trim($token);
        
        $sql = "SELECT * FROM usuarios 
                WHERE email = '$email' 
                AND token_validacion = '$token' 
                AND token_expira > NOW() 
                AND email_validado = 0";
        
        $result = $this->conexion->query($sql);
        
        
        return !empty($result);
    }

    public function activarCuenta($email)
    {
        $email = trim($email);
        
        $sql = "UPDATE usuarios 
                SET email_validado = 1, 
                    token_validacion = NULL, 
                    token_expira = NULL 
                WHERE email = '$email'";
        
        $result = $this->conexion->query($sql);
        
        
        return $result;
    }
    
    public function estaValidada($email)
    {
        $sql = "SELECT email_validado FROM usuarios WHERE email = '$email'";
        $result = $this->conexion->query($sql);
        return !empty($result) && $result[0]['email_validado'] == 1;
    }

public function actualizarToken($email, $token, $expira)
{
    $sql = "UPDATE usuarios 
            SET token_validacion = '$token', 
                token_expira = '$expira' 
            WHERE email = '$email' 
            AND email_validado = 0";
    $this->conexion->query($sql);
}

public function getUsuarioByEmail($email)
{
    $sql = "SELECT * FROM usuarios WHERE email = '$email'";
    $result = $this->conexion->query($sql);
    return !empty($result) ? $result[0] : null;
}
}