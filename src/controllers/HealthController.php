<?php

declare(strict_types=1);

final class HealthController
{
    private HealthService $healthService;

    public function __construct(HealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    public function check(): void
    {
        $startedAt = microtime(true);
        $payload = $this->healthService->buildReport($startedAt);
        http_response_code(200);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
