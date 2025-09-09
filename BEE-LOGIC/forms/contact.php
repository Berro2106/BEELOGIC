<?php
// =======================
// contact.php (Bee-Logic)
// =======================

// Muestra errores en desarrollo (XAMPP). Desactívalo en producción.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---- CONFIGURACIÓN SMTP (edita estas dos constantes) ----
// Usuario Gmail que enviará los correos (debe tener 2FA y contraseña de aplicación)
const SMTP_USER = 'beelogic433@gmail.com';
// Pega AQUÍ tu contraseña de aplicación (16 caracteres, sin espacios)
const SMTP_PASS = 'PON_AQUI_TU_CONTRASENA_DE_APLICACION';

// ---------------------------------------------------------
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer vía Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Helpers
function post_field(string $key): string {
  return trim($_POST[$key] ?? '');
}
function bad_request(string $msg, int $code = 422) {
  http_response_code($code);
  exit($msg);
}

// 1) Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  bad_request('Método no permitido', 405);
}

// 2) (Opcional) Honeypot anti-spam: si agregas un input oculto "website" en el form
if (!empty($_POST['website'] ?? '')) {
  bad_request('Bot detectado');
}

// 3) Validar campos
$name    = post_field('name');
$email   = post_field('email');
$subject = post_field('subject');
$message = post_field('message');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  bad_request('Por favor completa todos los campos.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  bad_request('Correo inválido.');
}

// 4) Sanitizar para incrustar en HTML
$safeName    = htmlspecialchars($name,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeEmail   = htmlspecialchars($email,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

// 5) Enviar con PHPMailer + Gmail SMTP
$mail = new PHPMailer(true);

try {
  // SMTP básico Gmail
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;
  $mail->Username   = 'beelogic433@gmail.com';
  $mail->Password   = 'mndrhplrlacjunig';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  // From: MUY IMPORTANTE en Gmail: debe ser igual a tu $mail->Username
  $mail->setFrom(SMTP_USER, 'Bee-Logic | Formulario Web');

  // A dónde llega el mensaje (puede ser el mismo correo)
  $mail->addAddress('beelogic433@gmail.com', 'Bee-Logic');

  // Para que puedas responder al usuario directamente desde el correo recibido
  $mail->addReplyTo($email, $name);

  // Contenido
  $mail->isHTML(true);
  $mail->Subject = "Contacto desde Bee-Logic: {$safeSubject}";
  $mail->Body    = "
    <b>Nombre:</b> {$safeName}<br>
    <b>Email:</b> {$safeEmail}<br>
    <b>Asunto:</b> {$safeSubject}<br><br>
    <b>Mensaje:</b><br>{$safeMessage}
  ";
  $mail->AltBody = "Nombre: {$name}\nEmail: {$email}\nAsunto: {$subject}\n\nMensaje:\n{$message}";

  $mail->send();

  // Tu plantilla tiene .loading / .error-message / .sent-message.
  // Muchas plantillas esperan 'OK' para mostrar el mensaje de éxito.
  echo 'OK';
  exit;

} catch (Exception $e) {
  // En producción, registra el error y muestra un mensaje genérico.
  http_response_code(500);
  echo 'Error al enviar: ' . $mail->ErrorInfo;
  exit;
}
