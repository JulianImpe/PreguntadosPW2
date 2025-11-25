<?php

class EditorModel{
    private $conexion;
    public function __construct($conexion){
        $this->conexion = $conexion;
    }

    public function obtenerDatosUsuario($usuarioId){
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
    public function obtenerPreguntasSugeridas($limite = 3){
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

    public function obtenerPreguntasReportadas($limite = 3){
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

    public function obtenerTodasLasPreguntasReportadas(){
        return $this->obtenerPreguntasReportadas(999999);
    }

    public function obtenerEstadisticasEditor($editorId){
        $editorId = (int)$editorId;
        $resultado = $this->conexion->query("
            SELECT COUNT(CASE WHEN Estado = 'Aprobada' THEN 1 END) as aprobadas,
                COUNT(CASE WHEN Estado = 'Rechazada' THEN 1 END) as rechazadas,
                0 as editadas, COUNT(*) as total
            FROM Pregunta_sugerida WHERE Revisada_por = $editorId
        ");

        return $resultado[0] ?? ['aprobadas' => 0, 'rechazadas' => 0, 'editadas' => 0, 'total' => 0];
    }

    public function aprobarPreguntaSugerida($preguntaId, $editorId){
        $preguntaId = (int)$preguntaId;
        $editorId = (int)$editorId;
        $result = $this->conexion->query("SELECT * FROM Pregunta_sugerida WHERE ID = $preguntaId");
        $sugerida = !empty($result) ? $result[0] : null;
        if (!$sugerida) {
            return false;
        }

        $texto = $this->conexion->getConexion()->real_escape_string($sugerida['Texto']);
        $medallaId = (int)$sugerida['Medalla_ID'];
        $creadaPor = (int)$sugerida['Sugerida_por_usuario_ID'];

        $sqlInsertPregunta = "
        INSERT INTO Pregunta (Texto, Medalla_ID, Estado_ID, Creada_por_usuario_ID, Aprobada_por)
        VALUES ('$texto', $medallaId, 2, $creadaPor, $editorId)
    ";
        $this->conexion->query($sqlInsertPregunta);
        $nuevaPreguntaId = $this->conexion->lastInsertId();

        $respuestas = $this->conexion->query("SELECT * FROM Respuesta_sugerida WHERE Pregunta_sugerida_ID = $preguntaId");
        foreach ($respuestas as $resp) {
            $textoResp = $this->conexion->getConexion()->real_escape_string($resp['Texto']);
            $esCorrecta = $resp['Es_Correcta'] ? 1 : 0;
            $this->conexion->query("
            INSERT INTO Respuesta (Pregunta_ID, Texto, Es_Correcta)
            VALUES ($nuevaPreguntaId, '$textoResp', $esCorrecta)
        ");
        }
        return $this->actualizarEstadoSugerida($preguntaId, $editorId, 'Aprobada');
    }
    public function rechazarPreguntaSugerida($preguntaId, $editorId){
        return $this->actualizarEstadoSugerida($preguntaId, $editorId, 'Rechazada');
    }
    public function aprobarPreguntaReportada($preguntaId, $editorId){
        return $this->resolverReporte($preguntaId, $editorId, 2, 'Aprobada por editor');
    }
    public function eliminarPreguntaReportada($preguntaId, $editorId){
        return $this->resolverReporte($preguntaId, $editorId, 3, 'Eliminada por editor');
    }
    public function actualizarPreguntaCompleta($preguntaId, $textoPregunta, $respuestas){
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
    private function formatearPreguntas($resultado, $tipo){
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

    private function obtenerRespuestasSugeridas($preguntaId){
        return $this->obtenerRespuestas($preguntaId, 'Respuesta_sugerida', 'Pregunta_sugerida_ID');
    }

    private function obtenerRespuestasPregunta($preguntaId){
        return $this->obtenerRespuestas($preguntaId, 'Respuesta', 'Pregunta_ID');
    }

    private function obtenerRespuestas($preguntaId, $tabla, $campo){
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

    private function actualizarEstadoSugerida($preguntaId, $editorId, $estado){
        $preguntaId = (int)$preguntaId;
        $editorId = (int)$editorId;

        $ok = $this->conexion->query("
        UPDATE Pregunta_sugerida 
        SET Estado = '$estado', Revisada_por = $editorId, Fecha_revision = NOW()
        WHERE ID = $preguntaId
    ");

        return $ok !== false;
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
    public function getMedallaClase($nombre){
        $clases = [
            'Medalla Roca' => 'roca', 'Medalla Cascada' => 'cascada',
            'Medalla Trueno' => 'trueno', 'Medalla Arcoíris' => 'arcoiris',
            'Medalla Alma' => 'alma', 'Medalla Pantano' => 'pantano',
            'Medalla Volcán' => 'volcan', 'Medalla Tierra' => 'tierra'
        ];
        return $clases[$nombre] ?? 'roca';
    }
//cambiarlo por las fotos q tnemos descargadas
    public function getMedallaEmoji($nombre){
        $emojis = [
            'Medalla Roca' => '🪨', 'Medalla Cascada' => '💧',
            'Medalla Trueno' => '⚡', 'Medalla Arcoíris' => '🌈',
            'Medalla Alma' => '👻', 'Medalla Pantano' => '🧠',
            'Medalla Volcán' => '🔥', 'Medalla Tierra' => '⛰️'
        ];
        return $emojis[$nombre] ?? '🏅';
    }





    public function getAllMedallas()
    {
        return $this->conexion->query("SELECT * FROM Medallas");
    }


    public function getMedallaById($id)
    {
        $id = (int)$id;
        $resultado = $this->conexion->query("SELECT * FROM Medallas WHERE ID = $id LIMIT 1");
        return !empty($resultado) ? $resultado[0] : null;
    }


    public function createMedalla($data)
    {
        $nombre = $this->conexion->getConexion()->real_escape_string($data['Nombre']);
        $color = $this->conexion->getConexion()->real_escape_string($data['Color']);
        $imagen = $this->conexion->getConexion()->real_escape_string($data['Imagen_url']);

        $sql = "INSERT INTO Medallas (Nombre, Color, Imagen_url) VALUES ('$nombre', '$color', '$imagen')";
        return $this->conexion->query($sql);
    }


    public function updateMedalla($id, $data)
    {
        $id = (int)$id;
        $nombre = $this->conexion->getConexion()->real_escape_string($data['Nombre']);
        $color = $this->conexion->getConexion()->real_escape_string($data['Color']);
        $imagen = $this->conexion->getConexion()->real_escape_string($data['Imagen_url']);

        $sql = "UPDATE Medallas SET Nombre = '$nombre', Color = '$color', Imagen_url = '$imagen' WHERE ID = $id";
        return $this->conexion->query($sql);
    }


    public function deleteMedalla($id)
    {
        $id = (int)$id;
        return $this->conexion->query("DELETE FROM Medallas WHERE ID = $id");
    }


    public function getTodasLasMedallas()
    {
        $query = "SELECT ID, Nombre, Color, Imagen_url FROM medallas";
        $result = $this->conexion->query($query);

        $medallas = [];
        while ($row = $result->fetch_assoc()) {
            $medallas[] = $row;
        }

        return $medallas;
    }

    public function eliminarMedallaPorId($id)
    {
        $id = (int)$id;
        return $this->conexion->query("DELETE FROM medallas WHERE ID = $id");
    }



    public function obtenerTodasLasPreguntas()
    {
        $sql = "
    SELECT 
        P.ID,
        P.Texto,
        EP.Nombre as Estado,
        M.Nombre as medalla_nombre
    FROM Pregunta P
    LEFT JOIN Estado_pregunta EP ON EP.ID = P.Estado_ID
    LEFT JOIN Medallas M ON M.ID = P.Medalla_ID
    ORDER BY P.ID DESC
    ";

        $resultado = $this->conexion->query($sql);

        if (is_array($resultado)) {
            return $resultado;
        }

        if (is_object($resultado) && method_exists($resultado, "fetch_all")) {
            return $resultado->fetch_all(MYSQLI_ASSOC);
        }

        return [];
    }

    public function eliminarPreguntaPorId($id)
    {
        $id = (int)$id;


        $this->conexion->query("DELETE FROM Respuesta WHERE Pregunta_ID = $id");


        return $this->conexion->query("DELETE FROM Pregunta WHERE ID = $id");
    }


    public function obtenerOpcionesDePregunta($preguntaId)
    {
        $preguntaId = intval($preguntaId);

        $query = "
        SELECT 
            ID,
            Texto,
            Es_Correcta AS EsCorrecta
        FROM Respuesta
        WHERE Pregunta_ID = $preguntaId
    ";


        return $this->conexion->query($query);
    }














}