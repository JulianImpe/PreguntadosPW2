<?php

class AdminController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->dashboard();
    }

    public function dashboard()
    {
        if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== "admin") {
            header("Location: /login/loginForm");
            exit;
        }

        $filtro = $_GET["filtro"] ?? "dia";

        $stats = [
            "total_usuarios"         => $this->model->obtenerCantidadUsuarios($filtro),
            "total_partidas"         => $this->model->obtenerCantidadPartidas($filtro),
            "total_preguntas"        => $this->model->obtenerCantidadPreguntas(),
            "preguntas_creadas"      => $this->model->obtenerCantidadPreguntasCreadas($filtro),
            "porcentaje_aciertos"    => $this->model->obtenerPorcentajeAciertosPorUsuario($filtro),
            "usuarios_por_pais"      => $this->model->obtenerUsuariosPorPais($filtro),
            "usuarios_por_sexo"      => $this->model->obtenerUsuariosPorSexo($filtro),
            "usuarios_por_edad"      => $this->model->obtenerUsuariosPorGrupoEdad($filtro)
        ];

        $data = [
            "filtro" => $filtro,
            "stats"  => $stats,
            "usuario" => $_SESSION["usuario"]
        ];

        $this->renderer->render("adminDashboard", $data);
    }

    // Gestionar Editores
    public function gestionarEditores()
    {
        if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== "admin") {
            header("Location: /login/loginForm");
            exit;
        }

        $ranking = $this->model->obtenerRankingParaEditores();

        $posicion = 1;
        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $posicion++;
            $jugador['es_editor'] = ($jugador['rol_id'] == 2);
        }

        $data = [
            'ranking' => $ranking,
            'mensaje_exito' => $_SESSION['mensaje_exito'] ?? null
        ];

        unset($_SESSION['mensaje_exito']);

        $this->renderer->render("gestionarEditores", $data);
    }

    // Promover a Editor
    public function promoverEditor()
    {
        if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== "admin") {
            header("Location: /login/loginForm");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /admin/gestionarEditores");
            exit;
        }

        $usuarioId = $_POST['usuario_id'] ?? null;

        if ($usuarioId) {
            $this->model->promoverAEditor($usuarioId);
            $_SESSION['mensaje_exito'] = "Usuario promovido a Editor exitosamente";
        }

        header("Location: /admin/gestionarEditores");
        exit;
    }

    // DESCARGA PDF CON DOMPDF
    public function descargarPDF()
    {
        if (!isset($_SESSION["usuario_id"]) || $_SESSION["rol"] !== "admin") {
            header("Location: /login/loginForm");
            exit;
        }

        $filtro = $_GET["filtro"] ?? "dia";

        $stats = [
            "total_usuarios"         => $this->model->obtenerCantidadUsuarios($filtro),
            "total_partidas"         => $this->model->obtenerCantidadPartidas($filtro),
            "total_preguntas"        => $this->model->obtenerCantidadPreguntas(),
            "preguntas_creadas"      => $this->model->obtenerCantidadPreguntasCreadas($filtro),
            "usuarios_por_pais"      => $this->model->obtenerUsuariosPorPais($filtro),
            "usuarios_por_sexo"      => $this->model->obtenerUsuariosPorSexo($filtro),
            "usuarios_por_edad"      => $this->model->obtenerUsuariosPorGrupoEdad($filtro)
        ];

        // Generar PDF con DOMPDF
        $this->generarPDFConDompdf($filtro, $stats);
        exit;
    }

    // GENERADOR PDF CON DOMPDF
    private function generarPDFConDompdf($filtro, $stats)
    {
        require_once __DIR__ . '/../helper/PdfGenerator.php';

        $pdfGenerator = new PdfGenerator();

        // Preparar título
        $tituloFiltro = [
            'dia' => 'Hoy',
            'semana' => 'Esta Semana',
            'mes' => 'Este Mes',
            'año' => 'Este Año'
        ];

        $titulo = 'Reporte Administrativo - ' . ($tituloFiltro[$filtro] ?? 'General');

        // Preparar estructura de tabla
        $tabla = [
            'headers' => ['Métrica', 'Valor'],
            'rows' => [
                ['Total Usuarios', $stats['total_usuarios']],
                ['Total Partidas', $stats['total_partidas']],
                ['Total Preguntas', $stats['total_preguntas']],
                ['Preguntas Creadas', $stats['preguntas_creadas']]
            ]
        ];

        if (!empty($stats['usuarios_por_sexo'])) {
            $tabla['rows'][] = ['', ''];
            $tabla['rows'][] = ['USUARIOS POR SEXO', ''];
            foreach ($stats['usuarios_por_sexo'] as $item) {
                $tabla['rows'][] = [$item['nombre'], $item['total']];
            }
        }


        // Agregar sección de usuarios por país
        if (!empty($stats['usuarios_por_pais'])) {
            $tabla['rows'][] = ['', ''];
            $tabla['rows'][] = ['USUARIOS POR PAÍS', ''];
            foreach ($stats['usuarios_por_pais'] as $item) {
                $tabla['rows'][] = [$item['nombre'], $item['total']];
            }
        }

        // Agregar sección de usuarios por edad
        $tabla['rows'][] = ['', ''];
        $tabla['rows'][] = ['USUARIOS POR EDAD', ''];
        $tabla['rows'][] = ['Menores (< 18)', $stats['usuarios_por_edad']['menores']];
        $tabla['rows'][] = ['Adultos (18-65)', $stats['usuarios_por_edad']['medio']];
        $tabla['rows'][] = ['Jubilados (> 65)', $stats['usuarios_por_edad']['jubilados']];

        // Generar y enviar PDF
        $pdfGenerator->generarReporteAdmin($titulo, $tabla);
    }
}
