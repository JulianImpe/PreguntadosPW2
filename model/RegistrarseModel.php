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
        // IMPORTANTE: Agregar validación de email_validado
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

    // CORREGIDO: Validar token con más debugging
    public function validarToken($email, $token)
    {
        // Agregar trim para eliminar espacios
        $email = trim($email);
        $token = trim($token);
        
        $sql = "SELECT * FROM usuarios 
                WHERE email = '$email' 
                AND token_validacion = '$token' 
                AND token_expira > NOW() 
                AND email_validado = 0";
        
        $result = $this->conexion->query($sql);
        
        // Debug: descomentar para ver qué está pasando
        // error_log("SQL: " . $sql);
        // error_log("Result: " . print_r($result, true));
        
        return !empty($result);
    }

    // CORREGIDO: Activar cuenta y verificar que se ejecutó
    public function activarCuenta($email)
    {
        $email = trim($email);
        
        $sql = "UPDATE usuarios 
                SET email_validado = 1, 
                    token_validacion = NULL, 
                    token_expira = NULL 
                WHERE email = '$email'";
        
        $result = $this->conexion->query($sql);
        
        // Debug: descomentar para ver si se actualizó
        // error_log("Activar cuenta SQL: " . $sql);
        // error_log("Resultado: " . print_r($result, true));
        
        return $result;
    }
    
    // NUEVO: Método para verificar si una cuenta está validada
    public function estaValidada($email)
    {
        $sql = "SELECT email_validado FROM usuarios WHERE email = '$email'";
        $result = $this->conexion->query($sql);
        return !empty($result) && $result[0]['email_validado'] == 1;
    }
}