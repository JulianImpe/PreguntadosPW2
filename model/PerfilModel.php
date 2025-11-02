<?php

class PerfilModel
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function obtenerPerfil()
    {
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
            $toast['clase'] = $toast['tipo'] === 'success' ? 'bg-green-600' : 'bg-red-600';
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
            'fecha_nacimiento' => $this->formatearFecha($datos['fecha_nac']),
            'edad' => $this->calcularEdad($datos['fecha_nac']),
            'foto_perfil' => !empty($datos['foto_perfil'])
                ? '/public/img/' . basename($datos['foto_perfil'])
                : '/public/img/default-avatar.png',
            'tiene_foto' => !empty($datos['foto_perfil']),
            'puntaje_total' => $datos['Puntaje_total'] ?? 0,
            'toast' => $toast
        ];
    }

    public function cambiarPassword()
    {
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
            $this->cambiarPasswordBD($usuarioId, $nueva);
            $_SESSION['toast'] = ['tipo' => 'success', 'mensaje' => 'Contraseña actualizada correctamente.'];
        }

        return '/perfil/ver';
    }

    public function obtenerPerfilCompartido()
    {
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
            'fecha_nacimiento' => $this->formatearFecha($datos['fecha_nac']),
            'edad' => $this->calcularEdad($datos['fecha_nac']),
            'foto_perfil' => !empty($datos['foto_perfil'])
                ? '/public/img/' . basename($datos['foto_perfil'])
                : '/public/img/default-avatar.png',
            'tiene_foto' => !empty($datos['foto_perfil']),
            'puntaje_total' => $datos['Puntaje_total'] ?? 0,
        ];
    }

    private function obtenerDatosCompletos($id)
    {
        $sql = "SELECT * FROM usuarios WHERE ID = '$id'";
        $res = $this->database->query($sql);
        return !empty($res) ? $res[0] : null;
    }

    private function cambiarPasswordBD($id, $pass)
    {
        $sql = "UPDATE usuarios SET password = '$pass' WHERE ID = '$id'";
        return $this->database->query($sql);
    }

    private function obtenerTextoSexo($sexoId)
    {
        $sexos = [1 => 'Masculino', 2 => 'Femenino', 3 => 'Otro', 4 => 'Prefiero no decirlo'];
        return $sexos[$sexoId] ?? 'No especificado';
    }

    private function formatearFecha($fecha)
    {
        if (empty($fecha)) return 'No especificada';
        $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
        $f = new DateTime($fecha);
        return $f->format('d') . ' de ' . $meses[(int)$f->format('m')] . ' de ' . $f->format('Y');
    }

    private function calcularEdad($fecha)
    {
        if (empty($fecha)) return 0;
        $nac = new DateTime($fecha);
        $hoy = new DateTime();
        return $hoy->diff($nac)->y;
    }


    //generador de qr de google use
    public function obtenerUrlQR($usuarioId)
    {
        if (!$usuarioId) return null;

        // URL exacta de tu perfil compartido
        $urlPerfil = "http://localhost/perfil/perfilCompartidoVista?id={$usuarioId}";

        // URL del QR generado por Google
        return "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($urlPerfil) . "&choe=UTF-8";
    }
}
