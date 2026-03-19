<?php

declare(strict_types=1);

function request(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
        ],
    ]);

    $response = file_get_contents($url, false, $context);
    $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
    preg_match('/\s(\d{3})\s/', $statusLine, $matches);
    $statusCode = isset($matches[1]) ? (int) $matches[1] : 500;
    $json = is_string($response) ? json_decode($response, true) : null;

    return [$statusCode, is_array($json) ? $json : []];
}

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$baseUrl = getenv('TEST_BASE_URL') ?: 'http://localhost:9000';

try {
    [$status, $payload] = request("{$baseUrl}/health");
    assertTrue($status === 200, 'Healthcheck não retornou HTTP 200.');
    assertTrue(isset($payload['status']), 'Campo status ausente no healthcheck.');
    assertTrue(isset($payload['timestamp']), 'Campo timestamp ausente no healthcheck.');
    assertTrue(isset($payload['response_time_ms']), 'Campo response_time_ms ausente no healthcheck.');
    assertTrue(isset($payload['checks']['database']['status']), 'Check de banco ausente no healthcheck.');
    assertTrue(isset($payload['system']['memory_usage_mb']), 'Métrica de memória ausente no healthcheck.');

    echo "TESTE_HEALTHCHECK_OK\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "TESTE_HEALTHCHECK_FALHOU: {$exception->getMessage()}\n");
    exit(1);
}
