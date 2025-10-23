<?php
class HomeVistaController {
    private $renderer;
    private $model;
    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base() {
        $this->homeVista();
    }

    public function homeVista() {
                $data = [];
        if (isset($_SESSION["usuario"])) {
            $data["usuario"] = $_SESSION["usuario"];
        }
            $this->renderer->render("homeVista", $data);
    }
}
