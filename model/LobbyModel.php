<?php

class LobbyModel {
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDatosUsuario($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $sql = "SELECT 
                ID,        
                usuario, 
                foto_perfil, 
                nombre_completo,
                email,
                fecha_nac
            FROM usuarios
            WHERE ID = $usuarioId
            LIMIT 1";
        $result = $this->conexion->query($sql);

        if (!empty($result)) {
            $datos = $result[0];
            $datos['usuario_id'] = $datos['ID'];
            $datos['ranking'] = '-';
            $datos['puntos'] = 0;
            $datos['partidas_jugadas'] = 0;
            $datos['partidas_ganadas'] = 0;
            $datos['nivel'] = 1;
            $datos['partidas_recientes'] = [];

            return $datos;
        }

        return [];
    }

    public function obtenerPartidasRecientes($usuario, $limite = 5)
    {
        return [];
    }
}