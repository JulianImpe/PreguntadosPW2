<?php
// Incluir los archivos de PHPMailer manualmente
require __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/PHPMailer-master/src/SMTP.php';
require __DIR__ . '/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tuemail@gmail.com';
    $mail->Password   = 'tu_app_password'; // usar contraseña de aplicaciones
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Destinatarios
    $mail->setFrom('tuemail@gmail.com', 'Tu Nombre');
    $mail->addAddress('destinatario@email.com', 'Destinatario');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Prueba PHPMailer';
    $mail->Body    = '<b>¡Hola! Esto es un correo de prueba</b>';
    $mail->AltBody = '¡Hola! Esto es un correo de prueba';

    $mail->send();
    echo 'Correo enviado correctamente';
} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}
