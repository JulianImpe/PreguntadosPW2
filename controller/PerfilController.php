<?php
class PerfilController{
    private $model;
    private $renderer;
    public function __construct($model, $renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }
    public function ver()
    {
        $data = $this->model->obtenerPerfil();
        if (isset($data['redirect'])) {
            header("Location: " . $data['redirect']);
            exit;
        }
        $this->renderer->render("perfil", $data);
    }
    public function cambiarPassword()
    {
        $redirect = $this->model->cambiarPassword();
        header("Location: $redirect");
        exit;
    }
    public function volverLobby(){
        header("Location: /lobby/base");
        exit;
    }
    public function perfilCompartidoVista(){
        $data = $this->model->obtenerPerfilCompartido();
        if (isset($data['error'])) {
            echo $data['error'];
            exit;
        }
        $this->renderer->render("perfilCompartido", $data);
    }
    public function actualizarCampo()
    {
        $redirect = $this->model->actualizarCampo();


        header("Location: $redirect");
        exit;
    }
    public function generarQR(){
        $usuarioId = $_GET['ID'] ?? null;
        if (!$usuarioId) {
            echo "Falta el ID del usuario";
            exit;
        }
        $qrUrl = $this->model->obtenerUrlQR($usuarioId);
        header("Location: $qrUrl");
        exit;
    }
//    public function generarQR() {
//        $usuarioId = $_GET['id'] ?? null; // ojo con mayúsculas
//        if (!$usuarioId) {
//            echo "Falta el ID del usuario";
//            exit;
//        }
//
//        // Generar URL segura con APP_HOST y APP_SCHEME
//        $qrUrl = APP_SCHEME . APP_HOST . '/perfil/perfilCompartidoVista?id=' . urlencode($usuarioId);
//
//        // Redirigir
//        header("Location: $qrUrl");
//        exit;
//    }
}
