<?php

declare(strict_types=1);

function request(string $method, string $url, ?array $payload = null, array $headers = []): array
{
    $headerLines = array_merge(['Content-Type: application/json'], $headers);
    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'ignore_errors' => true,
        ],
    ];

    if ($payload !== null) {
        $options['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $context = stream_context_create($options);
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
    [$loginStatus, $loginData] = request('POST', "{$baseUrl}/auth/login", [
        'username' => 'admin',
        'password' => 'admin123',
    ]);
    assertTrue($loginStatus === 200, 'Falha no login.');
    assertTrue(isset($loginData['access_token']), 'Token não retornado no login.');

    $token = (string) $loginData['access_token'];
    $authHeaders = ["Authorization: Bearer {$token}"];

    [$createStatus, $createData] = request('POST', "{$baseUrl}/tasks", [
        'title' => 'Teste integração',
        'description' => 'Criando tarefa autenticada',
        'status' => 'concluido',
        'created_at' => '2026-03-19',
    ], $authHeaders);
    assertTrue($createStatus === 201, 'Falha ao criar tarefa autenticada.');
    assertTrue(isset($createData['id']), 'ID não retornado na criação.');

    $taskId = (int) $createData['id'];

    [$updateStatus, $updateData] = request('PUT', "{$baseUrl}/tasks/{$taskId}", [
        'title' => 'Teste integração atualizado',
        'description' => 'Atualizado',
        'status' => 'em andamento',
        'created_at' => '2026-03-19',
    ], $authHeaders);
    assertTrue($updateStatus === 200, 'Falha ao atualizar tarefa autenticada.');
    assertTrue(($updateData['status'] ?? '') === 'em andamento', 'Status não atualizado corretamente.');

    [$deleteStatus] = request('DELETE', "{$baseUrl}/tasks/{$taskId}", null, $authHeaders);
    assertTrue($deleteStatus === 204, 'Falha ao excluir tarefa autenticada.');

    [$forbiddenCreateStatus] = request('POST', "{$baseUrl}/tasks", [
        'title' => 'Sem token',
        'description' => 'teste',
        'status' => 'pendente',
        'created_at' => '2026-03-19',
    ]);
    assertTrue($forbiddenCreateStatus === 401, 'Rota protegida permitiu requisição sem token.');

    echo "TESTE_INTEGRACAO_OK\n";
    exit(0);
} catch (Throwable $exception) {
    fwrite(STDERR, "TESTE_INTEGRACAO_FALHOU: {$exception->getMessage()}\n");
    exit(1);
}
