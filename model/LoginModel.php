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

        $sql = "
            SELECT u.*, r.Nombre AS rol_nombre, r.ID AS rol_id
            FROM usuarios u
            INNER JOIN Rol r ON r.ID = u.Rol_ID
            WHERE u.usuario = '$usuario'
            LIMIT 1
        ";

        $result = $this->conexion->query($sql);

        if (!empty($result)) {
            $user = $result[0];

            if ($user['password'] === $password) {

                $user['rol'] = $this->normalizarRol($user['rol_nombre']);
                return [$user];
            }
        }

        return [];
    }

    private function normalizarRol($rolNombre)
    {
        $roles = [
            'Jugador' => 'jugador',
            'Editor' => 'editor',
            'Administrador' => 'admin'
        ];

        return $roles[$rolNombre] ?? 'jugador';
    }
}