<?php

class PokemonController
{
    private $conexion;
    private $renderer;
    private $model;

    public function __construct($pokemonModel,$renderer)
    {
        $this->model = $pokemonModel;
        $this->renderer = $renderer;
    }

    public function base()
    {
        $this->listado();
    }

    public function nuevoForm()
    {
        $this->redirectIfNotAdmin();
        $this->renderer->render("nuevo");
    }

    public function nuevo()
    {
        $this->redirectIfNotAdmin();

        $ruta = "";
        if (isset($_FILES["imagen"]) && $_FILES["imagen"]["name"] != "") {
            $ruta = "/imagenes/" . basename($_FILES["imagen"]["name"]);
            move_uploaded_file($_FILES["imagen"]["tmp_name"], $ruta);
        }

        $this->model->nuevo($_POST["nombre"], $_POST["tipo"], $_POST["descripcion"], $ruta, $_POST["numero"]);

        $this->redirectToIndex();
    }


    public function editarForm()
    {
        $this->redirectIfNotAdmin();

        $datos["fila"] = $this->model->get($_GET["id"]);
        $datos["tipos"] = [
            ["tipo_valor" => "agua", "es_tipo_seleccionado" => $datos["fila"]["tipo"] == "agua"],
            ["tipo_valor" => "fuego", "es_tipo_seleccionado" => $datos["fila"]["tipo"] == "fuego"],
            ["tipo_valor" => "electrico", "es_tipo_seleccionado" => $datos["fila"]["tipo"] == "electrico"],
            ["tipo_valor" => "planta", "es_tipo_seleccionado" => $datos["fila"]["tipo"] == "planta"],

        ];

        $this->renderer->render("editar", $datos);
    }

    public function editar()
    {
        $this->redirectIfNotAdmin();

        $this->model->editar($_POST["id"], $_POST["numero"], $_POST["nombre"], $_POST["tipo"], $_POST["descripcion"]);

        $this->redirectToIndex();
    }

    public function borrar()
    {
        $this->redirectIfNotAdmin();
        $this->model->delete($_GET["id"]);
        $this->redirectToIndex();
    }

    public function ver()
    {
        $datos["fila"] = $this->model->get($_GET["id"]);
        $this->renderer->render("ver", $datos);
    }

    public function listado()
    {
        $busqueda = isset($_POST["busqueda"]) ? $_POST["busqueda"] : "";

        $resultado = $this->model->buscar($busqueda);


        $this->renderer->render("listado", array(
                "pokemon" => $resultado,
                "admin" => $this->isAdmin()
            )
        );
    }


    private function redirectIfNotAdmin()
    {
        if (!$this->isAdmin())
            $this->redirectToIndex();
    }

    private function redirectToIndex()
    {
        header("Location: /");
        exit();
    }

    private function isAdmin(): bool
    {
        return isset($_SESSION["usuario"]);
    }
}
