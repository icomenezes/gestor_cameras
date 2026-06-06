<?php
/**
 * Agente de provisionamento — roda no HOST (não no container)
 * Recebe POST do container trsystem e executa novo-cliente.sh
 *
 * Iniciar: nohup php /var/www/cameras/provision-agent.php >> /var/log/provision-agent.log 2>&1 &
 * Porta:   9099 (localhost only)
 */

$port   = 9099;
$secret = getenv('PROVISION_SECRET') ?: 'trocar-por-segredo-forte';
$script = __DIR__ . '/novo-cliente.sh';

$sock = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
if (!$sock) {
    fwrite(STDERR, "Erro ao abrir socket: {$errstr} ({$errno})\n");
    exit(1);
}

echo date('Y-m-d H:i:s') . " Agente ouvindo em 127.0.0.1:{$port}\n";
flush();

while (true) {
    $conn = @stream_socket_accept($sock, 30);
    if (!$conn) continue;

    $raw = '';
    $deadline = time() + 5;
    while (!feof($conn) && time() < $deadline) {
        $chunk = fread($conn, 4096);
        if ($chunk === false || $chunk === '') break;
        $raw .= $chunk;
        if (strpos($raw, "\r\n\r\n") !== false) break;
    }

    // Lê body do POST
    $body = '';
    if (preg_match('/Content-Length:\s*(\d+)/i', $raw, $m)) {
        $headerEnd = strpos($raw, "\r\n\r\n");
        $body = substr($raw, $headerEnd + 4, (int)$m[1]);
    }

    $data = json_decode($body, true);

    // Valida secret
    if (($data['secret'] ?? '') !== $secret) {
        fwrite($conn, "HTTP/1.1 403 Forbidden\r\nContent-Type: application/json\r\nConnection: close\r\n\r\n{\"error\":\"forbidden\"}");
        fclose($conn);
        continue;
    }

    $slug     = preg_replace('/[^a-z0-9\-]/', '', $data['slug']     ?? '');
    $domain   = preg_replace('/[^a-z0-9\-\.]/', '', $data['domain'] ?? '');
    $email    = filter_var($data['email']    ?? '', FILTER_VALIDATE_EMAIL);
    $password = $data['password'] ?? '';

    if (!$slug || !$domain || !$email || strlen($password) < 8) {
        fwrite($conn, "HTTP/1.1 422 Unprocessable\r\nContent-Type: application/json\r\nConnection: close\r\n\r\n{\"error\":\"dados invalidos\"}");
        fclose($conn);
        continue;
    }

    $logFile = '/var/log/provision-' . $slug . '.log';
    $cmd = sprintf(
        'bash %s --slug %s --domain %s --email %s --password %s >> %s 2>&1 &',
        escapeshellarg($script),
        escapeshellarg($slug),
        escapeshellarg($domain),
        escapeshellarg($email),
        escapeshellarg($password),
        escapeshellarg($logFile)
    );

    echo date('Y-m-d H:i:s') . " Provisionando: {$slug} ({$domain})\n";
    flush();
    shell_exec($cmd);

    fwrite($conn, "HTTP/1.1 202 Accepted\r\nContent-Type: application/json\r\nConnection: close\r\n\r\n{\"status\":\"provisioning\"}");
    fclose($conn);
}
