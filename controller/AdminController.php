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

        // filtros
        $filtro = $_GET["filtro"] ?? "dia"; // dia | semana | mes | año

        // obtener estadísticas del modelo
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

    // ---- DESCARGA PDF ----------
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
            "porcentaje_aciertos"    => $this->model->obtenerPorcentajeAciertosPorUsuario($filtro),
            "usuarios_por_pais"      => $this->model->obtenerUsuariosPorPais($filtro),
            "usuarios_por_sexo"      => $this->model->obtenerUsuariosPorSexo($filtro),
            "usuarios_por_edad"      => $this->model->obtenerUsuariosPorGrupoEdad($filtro)
        ];

        require_once "library/pdf/pdfGenerator.php";
        $titulo = "Reporte Administrativo - Filtro: $filtro";
        $pdf = new PDFGenerator();
        $pdf->generarReporteAdmin($titulo,$stats);

        exit;
    }
}
