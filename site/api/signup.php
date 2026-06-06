<?php
/**
 * Proxy de cadastro trial — recebe o POST do modal do site
 * e encaminha para a API Laravel do sistema principal.
 *
 * Fica em /var/www/camerasonline.net.br/api/signup.php
 */

header('Content-Type: application/json; charset=utf-8');

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Requisição inválida.']);
    exit;
}

// URL interna do sistema Laravel principal
// No servidor, ambos estão no mesmo host — pode ser localhost com a porta do container
// ou o domínio interno. Ajuste conforme a configuração do nginx.
$apiUrl = 'https://app.camerasonline.net.br/api/register';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT        => 180, // provisionamento pode demorar
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(503);
    echo json_encode(['error' => 'Não foi possível conectar ao servidor. Tente novamente em instantes.']);
    exit;
}

http_response_code($httpCode);
echo $response;
