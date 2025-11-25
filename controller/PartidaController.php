<?php

class PartidaController
{
    private $model;
    private $renderer;

    public function __construct($model, $renderer)
    {
        $this->model = $model;
        $this->renderer = $renderer;

        if (!isset($_SESSION['puntaje'])) {
            $_SESSION['puntaje'] = 0;
        }
    }

    private function soloJugadores()
{
    if ($_SESSION["rol"] === "admin") {
        header("Location: /admin/dashboard");
        exit;
    }
}


    private function addUserData($data = [])
    {
        if (isset($_SESSION['usuario_id'])) {
            $data['usuario'] = [
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre' => $_SESSION['nombre'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ];
        }
        return $data;
    }

    public function base()
    {
        if (!isset($_SESSION['usuario_id'])) {
            header("Location: /login/loginForm");
            exit;
        }


        if (isset($_SESSION['partida_id'])) {
            error_log("Limpiando partida anterior: " . $_SESSION['partida_id']);
            unset($_SESSION['partida_id']);
        }
        unset($_SESSION['puntaje']);
        unset($_SESSION['categoria_actual']);
        unset($_SESSION['pregunta_activa']);

        $partidaId = $this->model->crearPartida($_SESSION['usuario_id']);
        $_SESSION['partida_id'] = $partidaId;

        error_log("Nueva partida creada con ID: " . $partidaId);

        header("Location: /ruleta/base");
        exit;
    }

    public function enviarReporte()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /lobby/base');
            exit;
        }

        $preguntaId = $_POST['pregunta_id'] ?? null;
        $motivo = trim($_POST['motivo'] ?? '');

        if (!$preguntaId || empty($motivo)) {
            $_SESSION['error_reporte'] = 'Debes escribir un motivo para el reporte.';
            // <-- No vamos a /partida/base (que crea nueva partida). Volvemos a mostrar la misma vista de resultado
            header('Location: /partida/mostrarPartida');
            exit;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? null;
        $partidaId = $_SESSION['partida_id'] ?? null;

        if (!$usuarioId || !$partidaId) {
            $_SESSION['error_lobby'] = 'No tienes una partida activa.';
            header('Location: /lobby/base');
            exit;
        }

        // Verifica que la partida siga activa
        $partidaActiva = $this->model->verificarPartidaActiva($partidaId);
        if (!$partidaActiva) {
            $_SESSION['error_lobby'] = 'La partida ya finalizó.';
            header('Location: /lobby/base');
            exit;
        }

        // Cuenta reportes hechos
        $totalReportes = $this->model->contarReportesEnPartida($partidaId, $usuarioId);

        error_log("📝 Intentando reportar - Reportes actuales: $totalReportes");

        if ($totalReportes >= 2) {

            error_log("❌ Límite alcanzado - Finalizando partida");

            // Finaliza partida pero NO resetea el puntaje (puntaje se conserva en BD/registro)
            $this->model->finalizarPartida($partidaId, $_SESSION['puntaje'] ?? 0);

            // limpiar session relacionada con partida (pero conservar puntaje si querés)
            unset($_SESSION['partida_id']);
            unset($_SESSION['pregunta_activa']);
            unset($_SESSION['categoria_actual']);
            unset($_SESSION['respuesta_incorrecta_pendiente']);

            $_SESSION['error_lobby'] = 'Ya usaste tus 2 reportes. La partida ha finalizado.';
            header('Location: /lobby/base');
            exit;
        }

        // Guarda el reporte
        $guardado = $this->model->guardarReporte($preguntaId, $usuarioId, $motivo, $partidaId);

        if (!$guardado) {
            $_SESSION['error_reporte'] = 'Error al enviar el reporte. Intenta nuevamente.';
            // volvemos a la misma pantalla (mostrarPartida no crea partida)
            header('Location: /partida/mostrarPartida');
            exit;
        }

        $nuevoTotal = $totalReportes + 1;
        error_log("✅ Reporte guardado - Total ahora: $nuevoTotal");

        $huboIncorrecta = isset($_SESSION['respuesta_incorrecta_pendiente']);

        if ($huboIncorrecta) {

            unset($_SESSION['respuesta_incorrecta_pendiente']);

            if ($nuevoTotal >= 2) {
                error_log("🎯 Segundo reporte después de incorrecta - Finalizando partida");

                // Finaliza pero NO toca puntaje
                $this->model->finalizarPartida($partidaId, $_SESSION['puntaje'] ?? 0);

                $puntajeFinal = $_SESSION['puntaje'] ?? 0;

                unset($_SESSION['partida_id']);
                unset($_SESSION['pregunta_activa']);
                unset($_SESSION['categoria_actual']);

                $_SESSION['info_lobby'] = "✅ Reporte enviado. Partida finalizada con $puntajeFinal puntos. Has usado tus 2 reportes.";
                header('Location: /lobby/base');
                exit;
            }

            // Si no llegó a 2, volvemos a mostrar la partida para que el usuario siga jugando
            $_SESSION['exito_reporte'] = '✅ Reporte enviado. Continúa jugando. Te queda 1 reporte disponible.';
            header('Location: /partida/mostrarPartida');
            exit;
        } else {

            if ($nuevoTotal >= 2) {
                $_SESSION['exito_reporte'] = '✅ Reporte enviado. Has usado tus 2 reportes disponibles.';
            } else {
                $_SESSION['exito_reporte'] = '✅ Reporte enviado. Te queda ' . (2 - $nuevoTotal) . ' reporte disponible.';
            }

            // Si reportó una pregunta que respondió bien, permitimos seguir jugando:
            header('Location: /partida/mostrarPartida');
            exit;
        }
    }



    function mostrarPartida()
{
    $this->soloJugadores();
    
    error_log("=== MOSTRAR PARTIDA ===");
    error_log("partida_id en sesión: " . ($_SESSION['partida_id'] ?? 'NO EXISTE'));
    error_log("categoria_actual: " . ($_SESSION['categoria_actual'] ?? 'NO EXISTE'));


    if (!isset($_SESSION['partida_id'])) {
        error_log(" No hay partida activa, redirigiendo al lobby");
        header("Location: /lobby/base");
        exit;
    }


    $partidaActiva = $this->model->verificarPartidaActiva($_SESSION['partida_id']);
    if (!$partidaActiva) {
        error_log(" La partida ya no está activa en la BD");
        unset($_SESSION['partida_id']);
        unset($_SESSION['puntaje']);
        unset($_SESSION['pregunta_activa']);
        unset($_SESSION['categoria_actual']);
        header("Location: /lobby/base");
        exit;
    }


    if (isset($_SESSION['pregunta_activa']) && !isset($_SESSION['categoria_actual'])) {

        
        $preguntaActivaId = $_SESSION['pregunta_activa']['id'];
        
        error_log(" F5 detectado: Ya existe pregunta activa sin categoria_actual");
        error_log(" Usuario intentó recargar la página - Finalizando partida");
        

        $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaActivaId, 0);
        

        $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje'] ?? 0);
        

        unset($_SESSION['puntaje']);
        unset($_SESSION['partida_id']);
        unset($_SESSION['pregunta_activa']);
        unset($_SESSION['categoria_actual']);
        

        $_SESSION['mensaje_lobby'] = "⚠️ Partida finalizada: No se permite recargar la página durante una pregunta.";
        

        header("Location: /lobby/base");
        exit;
    }


    if (isset($_SESSION['categoria_actual'])) {

        unset($_SESSION['pregunta_activa']);
    }

    $medallaId = $_SESSION['categoria_actual'] ?? null;
    $partidaId = $_SESSION['partida_id'] ?? null;

    error_log("Llamando getPreguntaRender con partidaId: $partidaId");

    $preguntaRender = $this->model->getPreguntaRender($_SESSION['usuario_id'], $medallaId, $partidaId);

    if (!$preguntaRender) {
        $data = $this->addUserData([
            'esCorrecta' => false,
            'mensaje' => 'No hay preguntas disponibles',
            'puntaje' => $_SESSION['puntaje'] ?? 0,
            'pregunta' => null,
            'partida_terminada' => true
        ]);

        $this->renderer->render('partidaFinalizada', $data);
        return;
    }

    error_log("Pregunta obtenida ID: " . $preguntaRender['id']);

    if ($this->model->verificarPreguntaYaVista($partidaId, $preguntaRender['id'])) {
        error_log("⚠️ ADVERTENCIA: Esta pregunta YA fue vista en esta partida!");
    }

    $medallas = $this->model->getMedallaDeLaPregunta($preguntaRender['id']);

    $clase = 'bg-gray-200 text-gray-800 border-gray-300';
    $nivel = $preguntaRender['nivel_dificultad'] ?? null;
    if ($nivel === 'Fácil') {
        $clase = 'bg-green-100 text-green-800 border-green-300';
    } elseif ($nivel === 'Medio') {
        $clase = 'bg-yellow-100 text-yellow-800 border-yellow-300';
    } elseif ($nivel === 'Difícil') {
        $clase = 'bg-red-100 text-red-800 border-red-300';
    }

    $preguntaRender['dificultad_clase'] = $clase;


    $token = bin2hex(random_bytes(16));
    
    $_SESSION['pregunta_activa'] = [
        'id' => $preguntaRender['id'],
        'inicio' => time(),
        'token' => $token
    ];
    

    unset($_SESSION['categoria_actual']);

    $data = ["pregunta" => $preguntaRender];
    $data['pregunta_token'] = $token;

    if (isset($_SESSION['exito_reporte'])) {
        $data['exito_reporte'] = $_SESSION['exito_reporte'];
        unset($_SESSION['exito_reporte']);
    }

    if (isset($_SESSION['error_reporte'])) {
        $data['error_reporte'] = $_SESSION['error_reporte'];
        unset($_SESSION['error_reporte']);
    }

    $data = array_merge($data, [
        "medallas" => $medallas
    ]);

    $data = $this->addUserData($data);

    $this->renderer->render("crearPartida", $data);
}
    public function responder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /lobby/base');
            exit;
        }


        if (!isset($_POST['pregunta_token']) ||
            !isset($_SESSION['pregunta_activa']['token']) ||
            $_POST['pregunta_token'] !== $_SESSION['pregunta_activa']['token']) {

            error_log("⚠️ Token inválido o ausente - Posible recarga o reenvío");

            if (isset($_SESSION['partida_id'])) {
                $this->model->finalizarPartida($_SESSION['partida_id'], $_SESSION['puntaje'] ?? 0);
                unset($_SESSION['partida_id']);
                unset($_SESSION['puntaje']);
            }

            unset($_SESSION['pregunta_activa']);
            unset($_SESSION['categoria_actual']);

            $_SESSION['mensaje_lobby'] = "⚠️ Partida finalizada: Token de pregunta inválido.";
            header('Location: /lobby/base');
            exit;
        }

        $respuestaId = $_POST['respuesta'] ?? null;
        $preguntaId = $_POST['pregunta_id'] ?? null;

        if (!$respuestaId || !$preguntaId) {
            header('Location: /lobby/base');
            exit;
        }


        if (!isset($_SESSION['pregunta_activa']) ||
            $_SESSION['pregunta_activa']['id'] != $preguntaId) {

            error_log("❌ ID de pregunta no coincide con la activa");
            header('Location: /lobby/base');
            exit;
        }


        $tiempoAgotado = false;
        if (isset($_SESSION['pregunta_activa'])) {
            $inicio = $_SESSION['pregunta_activa']['inicio'];
            $transcurrido = time() - $inicio;
            if ($transcurrido > 18) {
                $tiempoAgotado = true;
            }
        }


        $data = $this->model->procesarRespuesta($preguntaId, $respuestaId, $tiempoAgotado);


        error_log("Registrando respuesta: Partida=" . $_SESSION['partida_id'] . ", Pregunta=$preguntaId, Correcta=" . ($data['esCorrecta'] ? '1' : '0'));
        $this->model->registrarRespuesta($_SESSION['partida_id'], $preguntaId, $data['esCorrecta'] ? 1 : 0);


        if ($data['esCorrecta'] && !$tiempoAgotado) {
            unset($_SESSION['pregunta_activa']);
            unset($_SESSION['categoria_actual']);
            $data['partida_terminada'] = false;
            $data = $this->addUserData($data);
            $this->renderer->render('partidaFinalizada', $data);
            return;
        }


        unset($_SESSION['pregunta_activa']);
        unset($_SESSION['categoria_actual']);


        $_SESSION['respuesta_incorrecta_pendiente'] = [
            'pregunta_id' => $preguntaId,
            'puntaje' => $_SESSION['puntaje'] ?? 0
        ];

        $data['partida_terminada'] = false;
        $data = $this->addUserData($data);

        $this->renderer->render('partidaFinalizada', $data);
    }


    public function continuarSinReportar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /lobby/base');
            exit;
        }

        $partidaId = $_SESSION['partida_id'] ?? null;

        if (!$partidaId) {
            header('Location: /lobby/base');
            exit;
        }


        if (isset($_SESSION['respuesta_incorrecta_pendiente'])) {
            error_log("⚠️ Usuario decidió no reportar después de respuesta incorrecta - Finalizando partida");

            $this->model->finalizarPartida($partidaId, $_SESSION['puntaje'] ?? 0);

            $puntajeFinal = $_SESSION['puntaje'] ?? 0;

            unset($_SESSION['puntaje']);
            unset($_SESSION['partida_id']);
            unset($_SESSION['pregunta_activa']);
            unset($_SESSION['categoria_actual']);
            unset($_SESSION['respuesta_incorrecta_pendiente']);

            $_SESSION['info_lobby'] = "Partida finalizada con $puntajeFinal puntos.";
            header('Location: /lobby/base');
            exit;
        }


        header('Location: /partida/base');
        exit;
    }
}