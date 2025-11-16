<?php
session_start();

include("helper/ConfigFactory.php");


$configFactory = new ConfigFactory();
$router = $configFactory->get("router");

$router->executeController($_GET["controller"], $_GET["method"]);

/*$router->get('/editor/medallas', [EditorController::class, 'gestionarMedallas']);
$router->get('/editor/medalla/crear', [EditorController::class, 'crearMedalla']);
$router->get('/editor/medalla/editar', [EditorController::class, 'editarMedalla']);
$router->post('/editor/medalla/guardar', [EditorController::class, 'guardarMedalla']);
$router->get('/editor/medalla/eliminar', [EditorController::class, 'eliminarMedalla']);*/
