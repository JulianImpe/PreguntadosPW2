<?php

class RankingController{
    private $model;
    private $renderer;

    public function __construct($model,$renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }



    private function addUserData($data = []) {
        if (isset($_SESSION['usuario_id'])) {
            $data['usuario'] = [
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ];
        }
        return $data;
    }

    public function verRanking(){
        $ranking = $this->model->obtenerRanking();
        $posicion = 1;

        foreach ($ranking as &$jugador) {
            $jugador['posicion'] = $posicion++;
        }

        $data = $this->addUserData([
            "ranking" => $ranking
        ]);


        $this->renderer->render("ranking", $data);
    }



}