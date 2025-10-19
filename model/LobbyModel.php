<?php

class LobbyModel {
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDatosUsuario($usuario)
    {
        $sql = "SELECT 
                    usuario,
                    nombre_completo,
                    email,
                    fecha_nac,
                    foto_perfil
                FROM usuarios
                WHERE usuario = '$usuario'
                LIMIT 1";
        $result = $this->conexion->query($sql);

        if (!empty($result)) {
            $datos = $result[0];

            // Valores por defecto para estadísticas
            $datos['ranking'] = '-';
            $datos['puntos'] = 0;
            $datos['partidas_jugadas'] = 0;
            $datos['partidas_ganadas'] = 0;
            $datos['nivel'] = 1;

            // Devolvemos un array vacío para partidas recientes
            $datos['partidas_recientes'] = [];

            return $datos;
        }

        return [];
    }

    // Método reemplazo para no romper el controlador
    public function obtenerPartidasRecientes($usuario, $limite = 5)
    {
        return []; // Como no hay tabla de partidas
    }
}
