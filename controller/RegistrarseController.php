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

        // 1. Campos vacíos
        if (empty($usuario) || empty($password) || empty($repetir) || empty($email) || empty($fecha) || empty($nombre)|| empty($sexo)) {
            $data['error'] = "Todos los campos son obligatorios";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 2. Contraseñas iguales
        if ($password !== $repetir) {
            $data['error'] = "Las contraseñas no coinciden";
            $this->renderer->render("registrarse", $data);
            return;
        }


        // 3. Longitud y complejidad
        if (strlen($password) < 6 || !preg_match('/[A-Z]/', $password)) {
            $data['error'] = "La contraseña debe tener al menos 6 caracteres y contener una letra mayúscula";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 4. Email válido
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['error'] = "El email no es válido";
            $this->renderer->render("registrarse", $data);
            return;
        }

        // 6. Ya existe usuario, email o nombre completo
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
        // --- Si pasa todas las validaciones ---
$foto_perfil = $this->subirFoto();

// Obtener el ID del sexo (a partir del nombre)
$sexo_id = $this->model->getSexoIdByNombre($sexo);

// Si no existe el ID, error
if (!$sexo_id) {
    $data['error'] = "Sexo inválido.";
    $this->renderer->render("registrarse", $data);
    return;
}
$this->model->registrarUsuario($usuario, $password, $email, $fecha, $foto_perfil, $nombre, $sexo_id);

include 'helper/enviarEmail.php';
$this->mostrarMailEnviado();

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


    public function mostrarMailEnviado()
    {
        $data['success'] = "Te hemos enviado un correo de confirmación. Por favor, revisa tu bandeja de entrada.";
            $this->renderer->render("mailEnviado", $data); // sin datos por ahora
    }
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