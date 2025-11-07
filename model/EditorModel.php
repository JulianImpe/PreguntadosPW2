<?php

class EditorModel
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDatosUsuario($usuarioId)
    {
        $usuarioId = (int)$usuarioId;
        $result = $this->conexion->query("
            SELECT ID, usuario, foto_perfil, nombre_completo, email, fecha_nac
            FROM usuarios WHERE ID = $usuarioId LIMIT 1
        ");

        if (!empty($result)) {
            $result[0]['usuario_id'] = $result[0]['ID'];
            return $result[0];
        }
        return [];
    }

    //solo muestra 3 en la pantalla priicpal, se puede quitar pero queda menos estetico--
    public function obtenerPreguntasSugeridas($limite = 3)
    {
        $resultado = $this->conexion->query("
            SELECT ps.ID as id, ps.Texto as texto, m.Nombre as medalla_nombre,
                   u.usuario as sugerida_por
            FROM Pregunta_sugerida ps
            JOIN Medallas m ON ps.Medalla_ID = m.ID
            JOIN usuarios u ON ps.Sugerida_por_usuario_ID = u.ID
            WHERE ps.Estado = 'Pendiente'
            ORDER BY ps.Fecha_sugerencia DESC
            LIMIT $limite
        ");

        return $this->formatearPreguntas($resultado, 'sugerida');
    }

    public function obtenerPreguntasReportadas($limite = 3)
    {
        $resultado = $this->conexion->query("
            SELECT p.ID as id, p.Texto as texto, m.Nombre as medalla_nombre,
                   COUNT(r.ID) as cantidad_reportes,
                   GROUP_CONCAT(r.Motivo SEPARATOR ' | ') as motivo
            FROM Pregunta p
            JOIN Medallas m ON p.Medalla_ID = m.ID
            JOIN Reporte r ON r.Pregunta_ID = p.ID
            WHERE r.Estado = 'Pendiente'
            GROUP BY p.ID
            ORDER BY cantidad_reportes DESC
            LIMIT $limite
        ");

        return $this->formatearPreguntas($resultado, 'reportada');
    }

    public function obtenerTodasLasPreguntasReportadas()
    {
        return $this->obtenerPreguntasReportadas(999999);
    }

    public function obtenerEstadisticasEditor($editorId)
    {
        $editorId = (int)$editorId;
        $resultado = $this->conexion->query("
            SELECT COUNT(CASE WHEN Estado = 'Aprobada' THEN 1 END) as aprobadas,
                   COUNT(CASE WHEN Estado = 'Rechazada' THEN 1 END) as rechazadas,
                   0 as editadas, COUNT(*) as total
            FROM Pregunta_sugerida WHERE Revisada_por = $editorId
        ");

        return $resultado[0] ?? ['aprobadas' => 0, 'rechazadas' => 0, 'editadas' => 0, 'total' => 0];
    }

    public function aprobarPreguntaSugerida($preguntaId, $editorId)
    {
        return $this->actualizarEstadoSugerida($preguntaId, $editorId, 'Aprobada');
    }

    public function rechazarPreguntaSugerida($preguntaId, $editorId)
    {
        return $this->actualizarEstadoSugerida($preguntaId, $editorId, 'Rechazada');
    }

    public function aprobarPreguntaReportada($preguntaId, $editorId)
    {
        return $this->resolverReporte($preguntaId, $editorId, 2, 'Aprobada por editor');
    }

    public function eliminarPreguntaReportada($preguntaId, $editorId)
    {
        return $this->resolverReporte($preguntaId, $editorId, 3, 'Eliminada por editor');
    }

    public function actualizarPreguntaCompleta($preguntaId, $textoPregunta, $respuestas)
    {
        $preguntaId = (int)$preguntaId;
        $conn = $this->conexion->getConexion();
        $textoEscapado = $conn->real_escape_string($textoPregunta);

        $this->conexion->query("UPDATE Pregunta SET Texto = '$textoEscapado', Estado_ID = 2 WHERE ID = $preguntaId");

        foreach ($respuestas as $respuestaId => $datos) {
            $respuestaId = (int)$respuestaId;
            $textoRespuesta = $conn->real_escape_string($datos['texto']);
            $esCorrecta = !empty($datos['es_correcta']) ? 1 : 0;

            $this->conexion->query("
                UPDATE Respuesta SET Texto = '$textoRespuesta', Es_Correcta = $esCorrecta 
                WHERE ID = $respuestaId
            ");
        }

        if (isset($_SESSION['usuario_id'])) {
            $this->resolverReporte($preguntaId, (int)$_SESSION['usuario_id'], 2, 'Editada y aprobada por editor');
        }

        return true;
    }
    private function formatearPreguntas($resultado, $tipo)
    {
        $preguntas = [];
        foreach ($resultado as $row) {
            $respuestas = $tipo === 'sugerida'
                ? $this->obtenerRespuestasSugeridas($row['id'])
                : $this->obtenerRespuestasPregunta($row['id']);

            $pregunta = [
                'id' => $row['id'],
                'texto' => $row['texto'],
                'medalla_nombre' => strtoupper($row['medalla_nombre']),
                'medalla_clase' => $this->getMedallaClase($row['medalla_nombre']),
                'medalla_emoji' => $this->getMedallaEmoji($row['medalla_nombre']),
                'respuestas' => $respuestas
            ];

            if ($tipo === 'sugerida') {
                $pregunta['sugerida_por'] = $row['sugerida_por'];
            } else {
                $pregunta['cantidad_reportes'] = $row['cantidad_reportes'];
                $pregunta['multiple_reportes'] = $row['cantidad_reportes'] > 1;
                $pregunta['motivo'] = $row['motivo'];
            }

            $preguntas[] = $pregunta;
        }
        return $preguntas;
    }

    private function obtenerRespuestasSugeridas($preguntaId)
    {
        return $this->obtenerRespuestas($preguntaId, 'Respuesta_sugerida', 'Pregunta_sugerida_ID');
    }

    private function obtenerRespuestasPregunta($preguntaId)
    {
        return $this->obtenerRespuestas($preguntaId, 'Respuesta', 'Pregunta_ID');
    }

    private function obtenerRespuestas($preguntaId, $tabla, $campo)
    {
        $preguntaId = (int)$preguntaId;
        $resultado = $this->conexion->query("
            SELECT ID, Texto as texto, Es_Correcta as es_correcta
            FROM $tabla 
            WHERE $campo = $preguntaId
        ");

        $respuestas = [];
        foreach ($resultado as $row) {
            $respuestas[] = [
                'id' => $row['ID'],
                'texto' => $row['texto'],
                'es_correcta' => (bool)$row['es_correcta']
            ];
        }
        return $respuestas;
    }

    private function actualizarEstadoSugerida($preguntaId, $editorId, $estado)
    {
        $preguntaId = (int)$preguntaId;
        $editorId = (int)$editorId;
        return $this->conexion->query("
            UPDATE Pregunta_sugerida 
            SET Estado = '$estado', Revisada_por = $editorId, Fecha_revision = NOW()
            WHERE ID = $preguntaId
        ");
    }

    private function resolverReporte($preguntaId, $editorId, $estadoId, $accion)
    {
        $preguntaId = (int)$preguntaId;
        $editorId = (int)$editorId;
        $estadoId = (int)$estadoId;

        $this->conexion->query("UPDATE Pregunta SET Estado_ID = $estadoId WHERE ID = $preguntaId");

        $this->conexion->query("
            UPDATE Reporte 
            SET Estado = 'Resuelto', Revisado = 1, Revisado_por = $editorId,
                Fecha_resolucion = NOW(), Accion_tomada = '$accion'
            WHERE Pregunta_ID = $preguntaId AND Estado = 'Pendiente'
        ");

        return true;
    }

    private function getMedallaClase($nombre)
    {
        $clases = [
            'Medalla Roca' => 'roca', 'Medalla Cascada' => 'cascada',
            'Medalla Trueno' => 'trueno', 'Medalla Arcoíris' => 'arcoiris',
            'Medalla Alma' => 'alma', 'Medalla Pantano' => 'pantano',
            'Medalla Volcán' => 'volcan', 'Medalla Tierra' => 'tierra'
        ];
        return $clases[$nombre] ?? 'roca';
    }
//cambiarlo por las fotos q tnemos descargadas
    private function getMedallaEmoji($nombre)
    {
        $emojis = [
            'Medalla Roca' => '🪨', 'Medalla Cascada' => '💧',
            'Medalla Trueno' => '⚡', 'Medalla Arcoíris' => '🌈',
            'Medalla Alma' => '👻', 'Medalla Pantano' => '🧠',
            'Medalla Volcán' => '🔥', 'Medalla Tierra' => '⛰️'
        ];
        return $emojis[$nombre] ?? '🏅';
    }
}