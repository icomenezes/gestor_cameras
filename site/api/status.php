<?php
/**
 * status.php — Verifica se um slug já está provisionado e respondendo
 * GET /api/status.php?slug=academia
 * Retorna: { ready: true|false }
 */
header('Content-Type: application/json');

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) {
    echo json_encode(['ready' => false, 'error' => 'slug inválido']);
    exit;
}

$mapFile = '/etc/nginx/cameras_slugs.map';
$domain  = $slug . '.camerasonline.net.br';

// 1. Verifica se o slug está registrado no mapa nginx
if (!file_exists($mapFile)) {
    echo json_encode(['ready' => false]);
    exit;
}

$map = file_get_contents($mapFile);
if (strpos($map, $domain) === false) {
    echo json_encode(['ready' => false]);
    exit;
}

// 2. Extrai a porta do mapa
$port = null;
foreach (explode("\n", $map) as $line) {
    $line = trim($line);
    if (strpos($line, $domain) === 0) {
        if (preg_match('/(\d+);/', $line, $m)) {
            $port = (int)$m[1];
        }
        break;
    }
}

if (!$port) {
    echo json_encode(['ready' => false]);
    exit;
}

// 3. Testa se o container está respondendo via HTTP interno
$ch = curl_init("http://127.0.0.1:{$port}/up");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 4,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_NOBODY         => true,
]);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo json_encode(['ready' => $httpCode === 200]);
