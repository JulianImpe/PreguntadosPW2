<?php

class AdminModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerCantidadJugadores($filtro = null)
    {
        $where = $this->construirFiltroFecha("Fecha_registro", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM usuarios
            $where
        ");

        return $resultado[0]['total'] ?? 0;
    }

    public function obtenerCantidadPartidas($filtro = null)
    {
        $where = $this->construirFiltroFecha("Fecha_inicio", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM Partida
            $where
        ");

        return $resultado[0]['total'] ?? 0;
    }

    public function obtenerCantidadPreguntas()
    {
        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM Pregunta
        ");

        return $resultado[0]['total'] ?? 0;
    }

    public function obtenerCantidadPreguntasCreadas($filtro = null)
    {
        $where = $this->construirFiltroFecha("Fecha_creacion", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM Pregunta
            $where
        ");

        return $resultado[0]['total'] ?? 0;
    }
    public function obtenerUsuariosNuevos($filtro)
    {
        return $this->obtenerCantidadJugadores($filtro);
    }

    public function obtenerPorcentajeCorrectasPorUsuario($usuarioId)
    {
        $usuarioId = (int)$usuarioId;

        $resultado = $this->conexion->query("
            SELECT 
                SUM(CASE WHEN Correcta = 1 THEN 1 END) AS correctas,
                COUNT(*) AS total
            FROM Pregunta_partida
            WHERE Usuario_ID = $usuarioId
        ");

        if (empty($resultado) || $resultado[0]['total'] == 0) {
            return 0;
        }

        return round(($resultado[0]['correctas'] / $resultado[0]['total']) * 100, 2);
    }

    public function obtenerUsuariosPorPais()
    {
        return $this->conexion->query("
            SELECT Pais, COUNT(*) AS total
            FROM usuarios
            GROUP BY Pais
            ORDER BY total DESC
        ");
    }

    public function obtenerUsuariosPorSexo()
    {
        return $this->conexion->query("
            SELECT Sexo, COUNT(*) AS total
            FROM usuarios
            GROUP BY Sexo
        ");
    }

    public function obtenerUsuariosPorGrupoEdad()
    {
        return $this->conexion->query("
            SELECT 
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, Fecha_nac, CURDATE()) < 18 THEN 1 END) AS menores,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, Fecha_nac, CURDATE()) BETWEEN 18 AND 65 THEN 1 END) AS medio,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, Fecha_nac, CURDATE()) > 65 THEN 1 END) AS jubilados
            FROM usuarios
        ")[0] ?? ['menores'=>0, 'medio'=>0, 'jubilados'=>0];
    }

    private function construirFiltroFecha($campo, $filtro)
    {
        switch ($filtro) {
            case 'dia':
                return "WHERE DATE($campo) = CURDATE()";

            case 'semana':
                return "WHERE YEARWEEK($campo, 1) = YEARWEEK(CURDATE(), 1)";

            case 'mes':
                return "WHERE YEAR($campo) = YEAR(CURDATE())
                        AND MONTH($campo) = MONTH(CURDATE())";

            case 'anio':
                return "WHERE YEAR($campo) = YEAR(CURDATE())";

            default:
                return "";
        }
    }
}
