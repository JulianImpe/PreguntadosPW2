<?php
class PerfilModel{
    private $database;
    public function __construct($database){
        $this->database = $database;
    }
    public function obtenerPerfil(){
        if (empty($_SESSION['usuario_id'])) {
            return ['redirect' => '/login/loginForm'];
        }
        $usuarioId = $_SESSION['usuario_id'];
        $datos = $this->obtenerDatosCompletos($usuarioId);

        if (!$datos) {
            return ['error' => 'No hay datos del usuario'];
        }
        $toast = $_SESSION['toast'] ?? null;
        if ($toast) {
            $toast['clase'] = $toast['tipo'] === 'success'
                ? 'bg-green-600'
                : 'bg-red-600';
            unset($toast['tipo']);
        }
        unset($_SESSION['toast']);
        return [
            'ID' => $datos['ID'],
            'usuario_id' => $datos['ID'],
            'nombre_completo' => $datos['nombre_completo'],
            'usuario' => $datos['usuario'],
            'email' => $datos['email'],
            'sexo' => $this->obtenerTextoSexo($datos['Sexo_ID']),
            'sexo_id' => $datos['Sexo_ID'],
            'fecha_nac' => $this->formatearFecha($datos['fecha_nac']),
            'fecha_nac_raw' => $datos['fecha_nac'],
            'edad' => $this->calcularEdad($datos['fecha_nac']),
            'foto_perfil' => !empty($datos['foto_perfil'])
                ? '/public/img/' . basename($datos['foto_perfil'])
                : '/public/img/default-avatar.png',
            'tiene_foto' => !empty($datos['foto_perfil']),
            'puntaje_total' => $datos['Puntaje_total'] ?? 0,
            'pais' => $datos['pais'] ?? '',        // <--- agregalo
            'ciudad' => $datos['ciudad'] ?? '',
            'toast' => $toast
        ];
    }
    public function actualizarCampo(){
        if (empty($_SESSION['usuario_id'])) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Sesión expirada'];
            return '/login/loginForm';
        }

        $usuarioId = $_SESSION['usuario_id'];
        $campo = $_POST['campo'] ?? '';
        $valor = $_POST['valor'] ?? '';

        switch ($campo) {
            case 'email':
                if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Email inválido'];
                    return '/perfil/ver';
                }
                if ($this->emailExiste($valor, $usuarioId)) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Ese email ya está en uso'];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'email', $valor);
                $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Email actualizado correctamente'];
                break;
            case 'nombre':
                if (strlen($valor) < 3) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'El nombre debe tener al menos 3 caracteres'];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'nombre_completo', $valor);
                $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Nombre actualizado correctamente'];
                break;
            case 'usuario':
                if (strlen($valor) < 3) {
                    $_SESSION['toast'] = [
                        'tipo' => 'error',
                        'mensaje' => 'El usuario debe tener al menos 3 caracteres'
                    ];
                    return '/perfil/ver';
                }
                $sql = "SELECT ID FROM usuarios WHERE usuario = '$valor' AND ID != '$usuarioId'";
                $existe = $this->database->query($sql);

                if (!empty($existe)) {
                    $_SESSION['toast'] = [
                        'tipo' => 'error',
                        'mensaje' => 'Ese nombre de usuario ya está en uso'
                    ];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'usuario', $valor);
                $_SESSION['toast'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Usuario actualizado correctamente'
                ];
                break;
            case 'fecha':
                $fecha = new DateTime($valor);
                $hoy = new DateTime();
                $edad = $hoy->diff($fecha)->y;

                if ($edad < 13) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Debes tener al menos 13 años'];
                    return '/perfil/ver';
                }

                if ($edad > 120) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Fecha inválida'];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'fecha_nac', $valor);
                $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Fecha de nacimiento actualizada'];
                break;
            case 'sexo':
                if (!in_array($valor, ['1', '2', '3'])) {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Género inválido'];
                    return '/perfil/ver';
                }

                $this->actualizarDB($usuarioId, 'Sexo_ID', $valor);
                $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Género actualizado correctamente'];
                break;
            case 'foto':
                $resultado = $this->subirFoto($usuarioId);

                if ($resultado['success']) {
                    $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Foto actualizada correctamente'];
                } else {
                    $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => $resultado['error']];
                }
                break;
            case 'pais':
                if (strlen($valor) < 2) {
                    $_SESSION['toast'] = [
                        'tipo' => 'error',
                        'mensaje' => 'El país debe tener al menos 2 caracteres'
                    ];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'pais', $valor);
                $_SESSION['toast'] = [
                    'tipo' => 'success',
                    'mensaje' => 'País actualizado correctamente'
                ];
                break;

            case 'ciudad':
                if (strlen($valor) < 2) {
                    $_SESSION['toast'] = [
                        'tipo' => 'error',
                        'mensaje' => 'La ciudad debe tener al menos 2 caracteres'
                    ];
                    return '/perfil/ver';
                }
                $this->actualizarDB($usuarioId, 'ciudad', $valor);
                $_SESSION['toast'] = [
                    'tipo' => 'success',
                    'mensaje' => 'Ciudad actualizada correctamente'
                ];
                break;

            default:
                $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Campo no válido'];
        }

        return '/perfil/ver';
    }

    private function subirFoto($usuarioId){
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir la foto'];
        }

        $archivo = $_FILES['foto'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $extensionesPermitidas)) {
            return ['success' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o GIF'];
        }

        if ($archivo['size'] > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'La imagen es muy grande. Máximo 5MB'];
        }

        $directorio = __DIR__ . '/../public/img/';

        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $nombreArchivo = 'perfil_' . $usuarioId . '_' . time() . '.' . $extension;
        $rutaDestino = $directorio . $nombreArchivo;

        $usuarioAnterior = $this->obtenerDatosCompletos($usuarioId);

        if (!empty($usuarioAnterior['foto_perfil'])) {
            $rutaAnterior = $directorio . basename($usuarioAnterior['foto_perfil']);
            if (file_exists($rutaAnterior) && strpos($rutaAnterior, 'default') === false) {
                unlink($rutaAnterior);
            }
        }

        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $this->actualizarDB($usuarioId, 'foto_perfil', $nombreArchivo);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Error al guardar la foto'];
    }
    private function emailExiste($email, $usuarioIdActual){
        $sql = "SELECT ID FROM usuarios WHERE email = '$email' AND ID != '$usuarioIdActual'";
        $resultado = $this->database->query($sql);
        return !empty($resultado);
    }
    private function actualizarDB($id, $campo, $valor){
        $sql = "UPDATE usuarios SET $campo = '$valor' WHERE ID = '$id'";
        return $this->database->query($sql);
    }

    public function cambiarPassword(){
        if (empty($_SESSION['usuario_id'])) {
            return '/login/loginForm';
        }
        $usuarioId = $_SESSION['usuario_id'];
        $usuario = $this->obtenerDatosCompletos($usuarioId);

        $actual = $_POST['password_actual'] ?? '';
        $nueva = $_POST['password_nueva'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';

        if (!$usuario) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Usuario no encontrado.'];

        } elseif (empty($actual) || empty($nueva) || empty($confirmar)) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Todos los campos son obligatorios.'];

        } elseif ($nueva !== $confirmar) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Las contraseñas no coinciden.'];

        } elseif (strlen($nueva) < 6 || !preg_match('/[A-Z]/', $nueva)) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'Debe tener al menos 6 caracteres y una mayúscula.'];

        } elseif ($actual !== $usuario['password']) {
            $_SESSION['toast'] = ['tipo' => 'error', 'mensaje' => 'La contraseña actual es incorrecta.'];

        } else {
            $this->actualizarDB($usuarioId, 'password', $nueva);
            $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Contraseña actualizada correctamente.'];
        }

        return '/perfil/ver';
    }

    public function obtenerPerfilCompartido(){
        $usuarioId = $_GET['id'] ?? null;

        if (!$usuarioId) return ['error' => 'Usuario no encontrado'];

        $datos = $this->obtenerDatosCompletos($usuarioId);

        if (!$datos) return ['error' => 'No hay datos del usuario'];

        return [
            'ID' => $datos['ID'],
            'usuario_id' => $datos['ID'],
            'nombre_completo' => $datos['nombre_completo'],
            'usuario' => $datos['usuario'],
            'sexo' => $this->obtenerTextoSexo($datos['Sexo_ID']),
            'fecha_nac' => $this->formatearFecha($datos['fecha_nac']),
            'edad' => $this->calcularEdad($datos['fecha_nac']),
            'foto_perfil' => !empty($datos['foto_perfil'])
                ? '/public/img/' . basename($datos['foto_perfil'])
                : '/public/img/default-avatar.png',
            'tiene_foto' => !empty($datos['foto_perfil']),
            'puntaje_total' => $datos['Puntaje_total'] ?? 0,
        ];
    }

    private function obtenerDatosCompletos($id){
        $sql = "SELECT * FROM usuarios WHERE ID = '$id'";
        $res = $this->database->query($sql);
        return !empty($res) ? $res[0] : null;
    }
    private function obtenerTextoSexo($sexoId){
        $sexos = [
            1 => 'Masculino',
            2 => 'Femenino',
            3 => 'Prefiero no cargarlo'
        ];
        return $sexos[$sexoId] ?? 'No especificado';
    }
    private function formatearFecha($fecha){
        if (empty($fecha)) return 'No especificada';
        $meses = [
            1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
            7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
        ];
        $f = new DateTime($fecha);
        return $f->format('d') . ' de ' . $meses[(int)$f->format('m')] . ' de ' . $f->format('Y');
    }
    private function calcularEdad($fecha){
        if (empty($fecha)) return 0;

        $nac = new DateTime($fecha);
        $hoy = new DateTime();
        return $hoy->diff($nac)->y;
    }
    public function obtenerUrlQR($usuarioId)
    {
        if (!$usuarioId) return null;
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $urlPerfil = "{$protocol}{$host}/perfil/perfilCompartidoVista?id={$usuarioId}";
        return "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" .
            urlencode($urlPerfil) . "&choe=UTF-8";
    }
}
