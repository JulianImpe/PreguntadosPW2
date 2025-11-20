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

    public function registrarUsuario($usuario, $password, $email, $fecha_nac, $foto_perfil, $nombre_completo, $sexo_id, $token)
    {
        // Calcular fecha de expiración (30 minutos)
        $expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        
        // CORRECCIÓN: Faltaba la comilla después de $sexo_id
        $sql = "INSERT INTO usuarios (usuario, password, email, fecha_nac, foto_perfil, nombre_completo, Sexo_ID, token_validacion, token_expira, email_validado) 
                VALUES ('$usuario', '$password', '$email', '$fecha_nac', '$foto_perfil', '$nombre_completo', '$sexo_id', '$token', '$expira', 0)";
        //                                                                                                            ↑ AQUÍ ESTABA EL ERROR
        
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

    // NUEVO: Validar token
    public function validarToken($email, $token)
    {
        $sql = "SELECT * FROM usuarios 
                WHERE email = '$email' 
                AND token_validacion = '$token' 
                AND token_expira > NOW() 
                AND email_validado = 0";
        $result = $this->conexion->query($sql);
        return !empty($result);
    }

    // NUEVO: Activar cuenta
    public function activarCuenta($email)
    {
        $sql = "UPDATE usuarios 
                SET email_validado = 1, 
                    token_validacion = NULL, 
                    token_expira = NULL 
                WHERE email = '$email'";
        $this->conexion->query($sql);
    }
}