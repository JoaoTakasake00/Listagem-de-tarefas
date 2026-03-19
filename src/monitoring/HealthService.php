<?php

declare(strict_types=1);

final class HealthService
{
    private PDO $pdo;
    private string $projectRoot;

    public function __construct(PDO $pdo, string $projectRoot)
    {
        $this->pdo = $pdo;
        $this->projectRoot = $projectRoot;
    }

    public function buildReport(float $startedAt): array
    {
        $databaseCheck = $this->checkDatabase();
        $jwtCheck = $this->checkJwtConfig();
        $authCheck = $this->checkAuthConfig();
        $responseTimeMs = (int) round((microtime(true) - $startedAt) * 1000);

        $overallStatus = ($databaseCheck['status'] === 'ok' && $jwtCheck['status'] === 'ok' && $authCheck['status'] === 'ok')
            ? 'ok'
            : 'degraded';

        return [
            'status' => $overallStatus,
            'timestamp' => gmdate('c'),
            'response_time_ms' => $responseTimeMs,
            'checks' => [
                'database' => $databaseCheck,
                'jwt' => $jwtCheck,
                'auth' => $authCheck,
            ],
            'system' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'cpu_load' => $this->cpuLoad(),
                'disk_free_mb' => round((float) disk_free_space($this->projectRoot) / 1024 / 1024, 2),
                'disk_total_mb' => round((float) disk_total_space($this->projectRoot) / 1024 / 1024, 2),
                'php_version' => PHP_VERSION,
            ],
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $statement = $this->pdo->query('SELECT 1');
            $result = $statement->fetchColumn();

            return [
                'status' => ((int) $result === 1) ? 'ok' : 'fail',
                'message' => 'Conexão com banco validada.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'fail',
                'message' => 'Falha na conexão com banco.',
            ];
        }
    }

    private function checkJwtConfig(): array
    {
        $secret = getenv('JWT_SECRET');
        $issuer = getenv('JWT_ISSUER');
        $algorithm = getenv('JWT_ALGORITHM');

        if (!is_string($secret) || trim($secret) === '') {
            return ['status' => 'fail', 'message' => 'JWT_SECRET ausente.'];
        }

        if (!is_string($issuer) || trim($issuer) === '') {
            return ['status' => 'fail', 'message' => 'JWT_ISSUER ausente.'];
        }

        if (!is_string($algorithm) || trim($algorithm) === '') {
            return ['status' => 'fail', 'message' => 'JWT_ALGORITHM ausente.'];
        }

        if (strtoupper(trim($algorithm)) !== 'HS256') {
            return ['status' => 'fail', 'message' => 'JWT_ALGORITHM deve ser HS256.'];
        }

        return ['status' => 'ok', 'message' => 'Configuração JWT válida.'];
    }

    private function checkAuthConfig(): array
    {
        $username = getenv('AUTH_USERNAME');
        $passwordHash = getenv('AUTH_PASSWORD_HASH');

        if (!is_string($username) || trim($username) === '') {
            return ['status' => 'fail', 'message' => 'AUTH_USERNAME ausente.'];
        }

        if (!is_string($passwordHash) || trim($passwordHash) === '') {
            return ['status' => 'fail', 'message' => 'AUTH_PASSWORD_HASH ausente.'];
        }

        if ((password_get_info($passwordHash)['algo'] ?? null) === null) {
            return ['status' => 'fail', 'message' => 'AUTH_PASSWORD_HASH inválido.'];
        }

        return ['status' => 'ok', 'message' => 'Configuração de autenticação válida.'];
    }

    private function cpuLoad(): float
    {
        if (!function_exists('sys_getloadavg')) {
            return 0.0;
        }

        $load = sys_getloadavg();
        if (!is_array($load) || !isset($load[0])) {
            return 0.0;
        }

        return round((float) $load[0], 2);
    }
}
