<?php

class AdminModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerCantidadUsuarios($filtro = null)
    {
        $where = $this->construirFiltroFecha("u.Fecha_creacion", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM usuarios u
            $where
        ");

        return $resultado[0]['total'] ?? 0;
    }

    public function obtenerCantidadPartidas($filtro = null)
    {
        $where = $this->construirFiltroFecha("p.Hora_inicio", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM Partida p
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
        $where = $this->construirFiltroFecha("p.Fecha_creacion", $filtro);

        $resultado = $this->conexion->query("
            SELECT COUNT(*) AS total
            FROM Pregunta p
            $where
        ");

        return $resultado[0]['total'] ?? 0;
    }

    public function obtenerPorcentajeAciertosPorUsuario($filtro)
    {
        // Este método no tiene sentido con el filtro así
        // Lo dejo retornando 0 por ahora
        return 0;
    }

    public function obtenerUsuariosPorPais($filtro)
    {
        $where = $this->construirFiltroFecha("u.Fecha_creacion", $filtro);
        
        $resultado = $this->conexion->query("
            SELECT p.Nombre AS nombre, COUNT(u.ID) AS total
            FROM usuarios u
            INNER JOIN Mapa m ON u.Mapa_ID = m.ID
            INNER JOIN Provincia prov ON m.Provincia_ID = prov.ID
            INNER JOIN Pais p ON prov.Pais_ID = p.ID
            $where
            GROUP BY p.Nombre
            ORDER BY total DESC
        ");

        return $resultado ?? [];
    }

    public function obtenerUsuariosPorSexo($filtro)
    {
        $where = $this->construirFiltroFecha("u.Fecha_creacion", $filtro);
        
        $resultado = $this->conexion->query("
            SELECT s.Nombre AS nombre, COUNT(u.ID) AS total
            FROM usuarios u
            INNER JOIN Sexo s ON u.Sexo_ID = s.ID
            $where
            GROUP BY s.Nombre
        ");

        return $resultado ?? [];
    }

    public function obtenerUsuariosPorGrupoEdad($filtro)
    {
        $where = $this->construirFiltroFecha("u.Fecha_creacion", $filtro);
        
        $resultado = $this->conexion->query("
            SELECT 
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) < 18 THEN 1 ELSE 0 END) AS menores,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) BETWEEN 18 AND 65 THEN 1 ELSE 0 END) AS medio,
                SUM(CASE WHEN TIMESTAMPDIFF(YEAR, u.fecha_nac, CURDATE()) > 65 THEN 1 ELSE 0 END) AS jubilados
            FROM usuarios u
            $where
        ");

        return $resultado[0] ?? ['menores'=>0, 'medio'=>0, 'jubilados'=>0];
    }

    private function construirFiltroFecha($campo, $filtro)
    {
        if (empty($filtro)) {
            return "";
        }

        switch ($filtro) {
            case 'dia':
                return "WHERE DATE($campo) = CURDATE()";

            case 'semana':
                return "WHERE YEARWEEK($campo, 1) = YEARWEEK(CURDATE(), 1)";

            case 'mes':
                return "WHERE YEAR($campo) = YEAR(CURDATE())
                        AND MONTH($campo) = MONTH(CURDATE())";

            case 'año':
            case 'anio':
                return "WHERE YEAR($campo) = YEAR(CURDATE())";

            default:
                return "";
        }
    }
    // Agregar estos métodos a AdminModel.php

public function obtenerRankingParaEditores()
{
    $sql = "SELECT
        u.ID as id,
        u.usuario, 
        u.Rol_ID as rol_id,
        MAX(p.Puntaje_obtenido) AS mejor_puntaje
    FROM Partida p
    INNER JOIN usuarios u ON p.Usuario_ID = u.ID
    GROUP BY u.ID, u.usuario, u.Rol_ID
    ORDER BY mejor_puntaje DESC
    LIMIT 20";

    return $this->conexion->query($sql);
}

public function promoverAEditor($usuarioId)
{
    $usuarioId = (int)$usuarioId;
    
    $this->conexion->query("
        UPDATE usuarios 
        SET Rol_ID = 2 
        WHERE ID = $usuarioId
    ");
}
}