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
        $sql = "SELECT usuario, 
        nombre_completo,
        email,fecha_nac,
        foto_perfil
            FROM usuarios
            WHERE ID = $usuarioId
            LIMIT 1";
        $result = $this->conexion->query($sql);

        if (!empty($result)) {
            $datos = $result[0];


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
