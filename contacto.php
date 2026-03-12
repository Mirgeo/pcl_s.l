<?php
declare(strict_types=1);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  echo "Método no permitido";
  exit;
}

// ===== CONFIG (IONOS recomendado) =====
$to        = "contacto@pcl-abogados.com";
$fromEmail = "contacto@pcl-abogados.com"; // ✅ mismo dominio
$siteName  = "PCL Abogados";
$subject   = "Nueva consulta desde la web - PCL Abogados";

// ===== Helpers =====
function clean_text(string $v): string {
  $v = trim($v);
  // evita inyección de cabeceras
  $v = str_replace(["\r", "\n"], " ", $v);
  return htmlspecialchars($v, ENT_QUOTES, "UTF-8");
}

function clean_multiline(string $v): string {
  $v = trim($v);
  return htmlspecialchars($v, ENT_QUOTES, "UTF-8");
}

// ===== Recoger datos =====
$nombre   = clean_text($_POST["fullName"] ?? "");
$telefono = clean_text($_POST["tel"] ?? "");
$email    = clean_text($_POST["email"] ?? "");
$area     = clean_text($_POST["area2"] ?? "");
$motivo   = clean_text($_POST["motivo2"] ?? "");
$mensaje  = clean_multiline($_POST["message2"] ?? "");

// Checkbox privacidad (tu HTML usa name="privacy2")
$privacyOk = isset($_POST["privacy2"]);

// ===== Validación =====
$errors = [];
if ($nombre === "")   $errors[] = "Falta el nombre.";
if ($telefono === "") $errors[] = "Falta el teléfono.";
if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email no válido.";
if ($mensaje === "")  $errors[] = "Falta el mensaje.";
if (!$privacyOk)      $errors[] = "Debe aceptar la política de privacidad.";

if (!empty($errors)) {
  http_response_code(400);
  echo render_result(false, "Revisa el formulario:", $errors);
  exit;
}

// ===== Email body =====
$ip = $_SERVER["REMOTE_ADDR"] ?? "Desconocida";
$dt = date("Y-m-d H:i:s");

$contenido =
"नई nueva consulta desde la web de {$siteName}\n\n" .
"Nombre: {$nombre}\n" .
"Teléfono: {$telefono}\n" .
"Email: {$email}\n" .
"Área: " . ($area ?: "---") . "\n" .
"Motivo: " . ($motivo ?: "---") . "\n\n" .
"Mensaje:\n{$mensaje}\n\n" .
"----\n" .
"Fecha: {$dt}\n" .
"IP: {$ip}\n";

// ===== Headers (IONOS friendly) =====
$headers  = "From: {$siteName} <{$fromEmail}>\r\n";
$headers .= "Reply-To: {$nombre} <{$email}>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// ===== Send =====
$ok = mail($to, $subject, $contenido, $headers);

if ($ok) {
  echo render_result(true, "Consulta enviada correctamente", [
    "Te responderemos lo antes posible.",
    "Si es urgente, llama al +34 663 932 422."
  ]);
} else {
  http_response_code(500);
  echo render_result(false, "Error al enviar el mensaje.", [
    "Prueba de nuevo en unos minutos.",
    "Si necesitas respuesta rápida, usa WhatsApp: +34 663 932 422."
  ]);
}

exit;


// ===== UI de respuesta =====
function render_result(bool $success, string $title, array $lines): string {
  $bg = $success ? "#eef9f2" : "#fff2f2";
  $bd = $success ? "rgba(25,197,106,.35)" : "rgba(255,0,0,.20)";
  $tt = $success ? "#0b4a2f" : "#7a1010";

  $lis = "";
  foreach ($lines as $l) {
    $lis .= "<li>" . htmlspecialchars($l, ENT_QUOTES, "UTF-8") . "</li>";
  }

  return "
<!doctype html>
<html lang='es'>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width,initial-scale=1'>
  <title>{$title}</title>
  <style>
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;background:#f4fbf7;color:#0b1b12;}
    .wrap{max-width:860px;margin:40px auto;padding:0 16px;}
    .card{background:{$bg};border:1px solid {$bd};border-radius:18px;padding:18px;}
    h1{margin:0 0 10px;color:{$tt};font-size:28px;}
    ul{margin:0;padding-left:18px;}
    a.btn{display:inline-block;margin-top:14px;padding:12px 16px;border-radius:14px;background:#19c56a;color:#063019;font-weight:800;text-decoration:none;}
    a.btn2{display:inline-block;margin-top:14px;margin-left:10px;padding:12px 16px;border-radius:14px;border:1px solid rgba(10,30,20,.18);background:#fff;color:#0b1b12;font-weight:800;text-decoration:none;}
  </style>
</head>
<body>
  <div class='wrap'>
    <div class='card'>
      <h1>{$title}</h1>
      <ul>{$lis}</ul>
      <a class='btn' href='index.html'>Volver al inicio</a>
      <a class='btn2' href='solicitar-consulta.html'>Volver a contacto</a>
    </div>
  </div>
</body>
</html>";
}
