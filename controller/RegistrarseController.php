<?php
class RegistrarseController{
    private $model;
    private $renderer;

    public function __construct($model, $renderer){
        $this->model = $model;
        $this->renderer = $renderer;
    }

    public function base(){
        $this->registrarse();
    }
    public function registrarseForm()
    {
        $this->renderer->render("registrarse");
    }

    public function registrarse()
    {
        $usuario = trim($_POST["usuario"]);
        $password = trim($_POST["password"]);
        $repetir = trim($_POST["repetir_password"]);
        $email = trim($_POST["email"]);
        $fecha = $_POST["fecha_nac"];
        $nombre = trim($_POST["nombre_completo"]);
        $sexo = trim($_POST["sexo"]);

        $pais = $_POST['pais'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';

        // --- VALIDACIONES ---
        if (empty($usuario) || empty($password) || empty($repetir) || empty($email) ||
            empty($fecha) || empty($nombre) || empty($sexo)) {

            $data['error'] = "Todos los campos son obligatorios";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if ($password !== $repetir) {
            $data['error'] = "Las contraseñas no coinciden";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password)) {
            $data['error'] = "La contraseña debe tener al menos 6 caracteres y contener una letra mayúscula";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['error'] = "El email no es válido";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if ($this->model->existeUsuario($usuario)) {
            $data['error'] = "El nombre de usuario ya está en uso";
            $this->renderer->render("registrarse", $data);
            return;
        }

        if ($this->model->existeEmail($email)) {
            $data['error'] = "El email ya está en uso";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // Foto
        $foto_perfil = $this->subirFoto();

        $sexo_id = $this->model->getSexoIdByNombre($sexo);
        if (!$sexo_id) {
            $data['error'] = "Sexo inválido.";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // GENERAR TOKEN
        $token = sprintf("%06d", mt_rand(0, 999999));

        // REGISTRAR USUARIO (ÚNICA LLAMADA CORRECTA)
        $this->model->registrarUsuario(
            $usuario,
            $password,
            $email,
            $fecha,
            $foto_perfil,
            $nombre,
            $sexo_id,
            $token,
            $pais,
            $ciudad
        );

        // ENVIAR MAIL
        $this->enviarEmailConToken($email, $nombre, $token);

        // MOSTRAR MENSAJE
        $this->mostrarMailEnviado($email);
    }


    private function enviarEmailConToken($email, $nombre, $token)
    {
        // Incluir PHPMailer
        require __DIR__ . '/../helper/PHPMailer-master/src/PHPMailer.php';
        require __DIR__ . '/../helper/PHPMailer-master/src/SMTP.php';
        require __DIR__ . '/../helper/PHPMailer-master/src/Exception.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'julian.m.imperiale@gmail.com';
            $mail->Password   = 'jisb uvwz igee qsih';
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('julian.m.imperiale@gmail.com', 'Pokémon Trivia');
            $mail->addAddress($email, $nombre);

            $mail->isHTML(true);
            $mail->Subject = 'Código de validación - PokeTrivia';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif;'>
                    <h2>¡Hola, $nombre!</h2>
                    <p>Gracias por registrarte en <b>PokeTrivia</b>.</p>
                    <p>Tu código de validación es:</p>
                    <div style='background: #f0f0f0; padding: 20px; text-align: center; margin: 20px 0;'>
                        <h1 style='letter-spacing: 8px; color: #333; margin: 0;'>$token</h1>
                    </div>
                    <p><strong>Este código expira en 30 minutos.</strong></p>
                    <p>Ingresa este código en la página de validación para activar tu cuenta.</p>
                </div>
            ";
            $mail->AltBody = "Hola $nombre, tu código de validación es: $token. Este código expira en 30 minutos.";

            $mail->send();
        } catch (\Exception $e) {
            // Log del error pero no mostrar al usuario
            error_log("Error enviando email: " . $e->getMessage());
        }
    }

    public function mostrarMailEnviado($email = null)
    {
        // Pasar el email para el formulario de validación
        $data['email'] = $email;
        $data['success'] = "Te hemos enviado un correo con un código de validación. Por favor, revisa tu bandeja de entrada.";
        $this->renderer->render("mailEnviado", $data);
    }

public function validarCodigo()
{
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');

    if (empty($email) || empty($token)) {
        $data['error'] = "Por favor ingresa el código de validación.";
        $data['email'] = $email;
        $this->renderer->render("mailEnviado", $data);
        return;
    }

    // Validar token en la base de datos
    if ($this->model->validarToken($email, $token)) {
        // Activar cuenta
        $resultado = $this->model->activarCuenta($email);
        
        // Verificar que realmente se activó
        if ($this->model->estaValidada($email)) {
            $data['success'] = "¡Tu cuenta ha sido activada exitosamente! Ya puedes iniciar sesión.";
            $this->renderer->render("cuentaActivada", $data);
        } else {
            $data['error'] = "Hubo un error al activar tu cuenta. Por favor contacta a soporte.";
            $data['email'] = $email;
            $this->renderer->render("mailEnviado", $data);
        }
    } else {
        $data['error'] = "Código inválido o expirado. Por favor verifica e intenta nuevamente.";
        $data['email'] = $email;
        $this->renderer->render("mailEnviado", $data);
    }
}

    public function getSexoIdByNombre($sexo)
    {
        return $this->model->getSexoIdByNombre($sexo);
    }

    private function subirFoto()
    {
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $nombre = uniqid() . "_" . $_FILES['foto_perfil']['name'];
            $directorio = __DIR__ . '/../public/img/';
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true);
            }
            $destino = $directorio . $nombre;
            move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino);
            return 'img/' . $nombre;
        }
        return null;
    }


    /*public function mostrarMailEnviado()
    {
        $data['success'] = "Te hemos enviado un correo de confirmación. Por favor, revisa tu bandeja de entrada.";
            $this->renderer->render("mailEnviado", $data); // sin datos por ahora
    }*/
    public function validarEmail(){
        $email = $_GET["email"] ?? "";
        $existe = $this->model->existeEmail($email);

        header("Content-Type: application/json");
        echo json_encode(["existe" => $existe]);
    }
    public function reverseGeocode()
    {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;

        if (!$lat || !$lon) {
            http_response_code(400);
            echo json_encode(['error' => 'Faltan latitud o longitud']);
            return;
        }
        $url = "https://nominatim.openstreetmap.org/reverse?lat=$lat&lon=$lon&format=json&accept-language=es";

        $options = [
            "http" => [
                "header" => "User-Agent: MiAppAquaNet/1.0 (contacto: camila@ejemplo.com)\r\n"
            ]
        ];
        $context = stream_context_create($options);

        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al obtener datos']);
            return;
        }
        header("Content-Type: application/json");
        echo $result;
    }

}