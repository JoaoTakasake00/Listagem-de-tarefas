<?php

declare(strict_types=1);

final class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(array $payload): void
    {
        $result = $this->authService->login($payload);
        http_response_code(200);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}
