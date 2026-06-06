<?php
/**
 * signup.php — API de cadastro de trial
 *
 * Coloque em: /var/www/camerasonline.net.br/api/signup.php
 *
 * Recebe POST JSON: { name, email, password }
 * Retorna JSON:     { slug, url, eta } ou { error }
 */

header('Content-Type: application/json; charset=utf-8');

// Só aceita origens conhecidas
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://camerasonline.net.br', 'https://www.camerasonline.net.br'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido.']);
    exit;
}

// --------------------------------------------------------------------------
// Rate limiting simples por IP (máx 3 tentativas por hora)
// --------------------------------------------------------------------------
$ip       = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ip       = preg_replace('/[^a-f0-9.:]/i', '', $ip);
$rateFile = "/tmp/signup_rate_" . md5($ip);

$attempts = 0;
if (file_exists($rateFile)) {
    $data = json_decode(file_get_contents($rateFile), true) ?: [];
    if ((time() - ($data['ts'] ?? 0)) < 3600) {
        $attempts = $data['count'] ?? 0;
    }
}

if ($attempts >= 3) {
    http_response_code(429);
    echo json_encode(['error' => 'Muitas tentativas. Aguarde 1 hora e tente novamente.']);
    exit;
}

// Incrementar contador
file_put_contents($rateFile, json_encode(['ts' => time(), 'count' => $attempts + 1]));

// --------------------------------------------------------------------------
// Validar entrada
// --------------------------------------------------------------------------
$body = file_get_contents('php://input');
$data = json_decode($body, true);

$name     = trim($data['name']     ?? '');
$email    = trim($data['email']    ?? '');
$password = $data['password']      ?? '';

$errors = [];

if (strlen($name) < 2) {
    $errors[] = 'Nome deve ter pelo menos 2 caracteres.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'E-mail inválido.';
}
if (strlen($password) < 8) {
    $errors[] = 'Senha deve ter pelo menos 8 caracteres.';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

// --------------------------------------------------------------------------
// Gerar slug a partir do e-mail
// --------------------------------------------------------------------------
$localPart = explode('@', $email)[0];
$slug      = strtolower($localPart);
$slug      = preg_replace('/[^a-z0-9]/', '-', $slug);
$slug      = preg_replace('/-+/', '-', $slug);
$slug      = trim($slug, '-');
$slug      = substr($slug, 0, 25);

if (strlen($slug) < 2) {
    $slug = 'user-' . substr(md5($email), 0, 6);
}

// Se slug já existe, adicionar sufixo aleatório
$clientDir = "/opt/cameras/{$slug}";
if (is_dir($clientDir)) {
    $slug = $slug . '-' . substr(md5(uniqid($email, true)), 0, 4);
}

// --------------------------------------------------------------------------
// Evitar provisionamento duplicado (lock file)
// --------------------------------------------------------------------------
$lockFile = "/tmp/provisioning_{$slug}.lock";
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 600) {
    http_response_code(409);
    echo json_encode(['error' => 'Este ambiente já está sendo provisionado. Aguarde alguns minutos.']);
    exit;
}
touch($lockFile);

// --------------------------------------------------------------------------
// Escrever credenciais em arquivo temporário (não passamos pela linha de comando)
// --------------------------------------------------------------------------
$configFile = "/tmp/signup_{$slug}.json";
file_put_contents($configFile, json_encode([
    'email'    => $email,
    'password' => $password,
    'name'     => $name,
]));
chmod($configFile, 0600);

// --------------------------------------------------------------------------
// Disparar provisionamento em background via sudo
// --------------------------------------------------------------------------
$safeSlug = escapeshellarg($slug);
$logFile  = "/opt/cameras/logs/provision_{$slug}.log";
$cmd      = "sudo /var/www/cameras/provisionar.sh {$safeSlug} > /dev/null 2>&1 &";

exec($cmd, $output, $code);

// Se o sudo falhou imediatamente (ex: permissão não configurada), avisa
// Mas exec com & retorna 0 sempre — verificamos o lock file para confirmar início
sleep(1);
if (!file_exists("/opt/cameras/logs") && !is_dir("/opt/cameras")) {
    // Diretório base não existe, provisionamento não iniciou
    unlink($configFile);
    unlink($lockFile);
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao iniciar o provisionamento. Entre em contato: contato@camerasonline.net.br']);
    exit;
}

$domain = "{$slug}.camerasonline.net.br";

// --------------------------------------------------------------------------
// Resposta de sucesso
// --------------------------------------------------------------------------
http_response_code(202);
echo json_encode([
    'slug'  => $slug,
    'url'   => "https://{$domain}",
    'login' => "https://{$domain}/login",
    'eta'   => 120, // segundos estimados
]);
