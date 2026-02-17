<?php
declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

function line(string $label, $value = ''): void
{
    if (is_array($value) || is_object($value)) {
        $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ': ' . $value . PHP_EOL;
}

function fetchUrl(string $url, int $timeout = 10): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'globinary-dsc-diagnostic/1.0',
    ]);
    $body = curl_exec($ch);
    $errNo = curl_errno($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    return [
        'ok' => $body !== false && $errNo === 0,
        'body' => is_string($body) ? trim($body) : '',
        'errno' => $errNo,
        'error' => $err,
        'http_code' => $info['http_code'] ?? 0,
        'primary_ip' => $info['primary_ip'] ?? '',
        'local_ip' => $info['local_ip'] ?? '',
        'total_time' => $info['total_time'] ?? 0,
    ];
}

echo "=== DSC Network Diagnostic ===" . PHP_EOL;
line('time_utc', gmdate('c'));
line('php_version', PHP_VERSION);
line('server_software', $_SERVER['SERVER_SOFTWARE'] ?? '');
line('server_name', $_SERVER['SERVER_NAME'] ?? '');
line('server_addr', $_SERVER['SERVER_ADDR'] ?? '');
line('remote_addr', $_SERVER['REMOTE_ADDR'] ?? '');
echo PHP_EOL;

$host = 'app.curierdragonstar.ro';
$url = 'https://app.curierdragonstar.ro/awb/cost';

echo "=== DNS ===" . PHP_EOL;
$ips = @gethostbynamel($host);
line('host', $host);
line('resolved_ips', $ips ?: []);
echo PHP_EOL;

echo "=== TCP 443 Reachability ===" . PHP_EOL;
$start = microtime(true);
$errno = 0;
$errstr = '';
$fp = @fsockopen('ssl://' . $host, 443, $errno, $errstr, 20);
$elapsed = microtime(true) - $start;
if (is_resource($fp)) {
    line('tcp_443', 'OK');
    line('tcp_elapsed_sec', number_format($elapsed, 3, '.', ''));
    fclose($fp);
} else {
    line('tcp_443', 'FAIL');
    line('tcp_errno', $errno);
    line('tcp_error', $errstr);
    line('tcp_elapsed_sec', number_format($elapsed, 3, '.', ''));
}
echo PHP_EOL;

echo "=== cURL DSC Endpoint (POST, no auth) ===" . PHP_EOL;
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => '{}',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
    ],
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => 'globinary-dsc-diagnostic/1.0',
]);
$resp = curl_exec($ch);
$errNo = curl_errno($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

line('curl_errno', $errNo);
line('curl_error', $err);
line('http_code', $info['http_code'] ?? 0);
line('primary_ip', $info['primary_ip'] ?? '');
line('local_ip', $info['local_ip'] ?? '');
line('namelookup_time', $info['namelookup_time'] ?? 0);
line('connect_time', $info['connect_time'] ?? 0);
line('appconnect_time', $info['appconnect_time'] ?? 0);
line('total_time', $info['total_time'] ?? 0);
if (is_string($resp)) {
    line('response_prefix', substr(trim($resp), 0, 300));
}
echo PHP_EOL;

echo "=== Outbound Public IP Checks ===" . PHP_EOL;
$ipChecks = [
    'ipify' => 'https://api.ipify.org',
    'ifconfig_me' => 'https://ifconfig.me/ip',
    'icanhazip' => 'https://icanhazip.com',
];
foreach ($ipChecks as $name => $ipUrl) {
    $r = fetchUrl($ipUrl, 10);
    line($name . '_ok', $r['ok'] ? 'yes' : 'no');
    line($name . '_ip', $r['body']);
    line($name . '_errno', $r['errno']);
    line($name . '_error', $r['error']);
    line($name . '_http', $r['http_code']);
    line($name . '_primary_ip', $r['primary_ip']);
    line($name . '_local_ip', $r['local_ip']);
    line($name . '_time', $r['total_time']);
    echo PHP_EOL;
}

echo "=== Done ===" . PHP_EOL;

