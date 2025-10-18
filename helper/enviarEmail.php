<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/PHPMailer-master/src/Exception.php';

$mail = new PHPMailer(true);

try {
    // ⚙️ Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'julian.m.imperiale@gmail.com'; // <-- tu correo real
    $mail->Password   = 'jisb uvwz igee qsih'; // <-- la que generaste
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // ✉️ Remitente y destinatario
    $mail->setFrom('julian.m.imperiale@gmail.com', 'Pokémon Trivia');
    $mail->addAddress($email, $nombre); // 👈 variables del controller

    // 🧾 Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Confirma tu cuenta en Pokémon Trivia';
    $mail->Body = "
        <h2>¡Hola, $nombre!</h2>
        <p>Gracias por registrarte en <b>Pokémon Trivia</b>.</p>
        <p>Para activar tu cuenta, hacé clic en el siguiente enlace:</p>
        <p>
            <a href='http://localhost/Preguntados/index.php?controller=Login&method=activar&email=$email'>
                Activar cuenta
            </a>
        </p>
    ";
    $mail->AltBody = "Hola $nombre, activá tu cuenta ingresando a: http://localhost/Preguntados/index.php?controller=Login&method=activar&email=$email";

    $mail->send();
    echo '✅ Correo de confirmación enviado correctamente.';
} catch (Exception $e) {
    echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
}
