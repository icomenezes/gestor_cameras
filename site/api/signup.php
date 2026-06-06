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

// Chama o container do sistema principal diretamente via HTTP interno.
// O nginx mapeia icomenezes.camerasonline.net.br → porta do container trsystem.
// Internamente no servidor, basta bater em localhost com o Host header correto,
// evitando roundtrip SSL e dependência de DNS externo.
//
// Para descobrir a porta: cat /opt/cameras/trsystem/credenciais.txt
// ou: docker ps | grep cameras_trsystem_app
$internalPort = getenv('CAMERAS_MAIN_PORT') ?: '8100'; // porta do container trsystem no host
$apiUrl = 'http://127.0.0.1:' . $internalPort . '/api/register';

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: application/json',
        'Host: icomenezes.camerasonline.net.br',
    ],
    CURLOPT_TIMEOUT        => 180, // provisionamento pode demorar ~2 min
    CURLOPT_SSL_VERIFYPEER => false,
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
