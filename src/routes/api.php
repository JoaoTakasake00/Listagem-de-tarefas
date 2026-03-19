<?php

declare(strict_types=1);

final class ApiRouter
{
    private TaskController $taskController;
    private AuthController $authController;
    private AuthService $authService;
    private HealthController $healthController;

    public function __construct(
        TaskController $taskController,
        AuthController $authController,
        AuthService $authService,
        HealthController $healthController
    ) {
        $this->taskController = $taskController;
        $this->authController = $authController;
        $this->authService = $authService;
        $this->healthController = $healthController;
    }

    public function dispatch(string $method, string $uriPath, array $payload): void
    {
        $segments = explode('/', trim($uriPath, '/'));
        $firstSegment = $segments[0] ?? '';
        $secondSegment = $segments[1] ?? '';

        if ($method === 'GET' && ($firstSegment === 'health' || ($firstSegment === 'api' && $secondSegment === 'health'))) {
            $this->healthController->check();
            return;
        }

        $authIndex = array_search('auth', $segments, true);
        $authAction = $authIndex !== false ? ($segments[$authIndex + 1] ?? null) : null;

        if ($method === 'POST' && $authAction === 'login') {
            $this->authController->login($payload);
            return;
        }

        $taskIndex = array_search('tasks', $segments, true);

        if ($taskIndex === false) {
            $this->errorResponse('Rota não encontrada.', 404);
            return;
        }

        $idSegment = $segments[$taskIndex + 1] ?? null;
        $id = $idSegment !== null ? (int) $idSegment : null;

        if (in_array($method, ['PUT', 'DELETE'], true) && ($id === null || $id <= 0)) {
            $this->errorResponse('ID inválido na rota.', 400);
            return;
        }

        if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            $this->authService->requireValidToken($this->getAuthorizationHeader());
        }

        if ($method === 'GET' && $id === null) {
            $this->taskController->listTasks();
            return;
        }

        if ($method === 'POST' && $id === null) {
            $this->taskController->createTask($payload);
            return;
        }

        if ($method === 'PUT' && $id !== null) {
            $this->taskController->updateTask($id, $payload);
            return;
        }

        if ($method === 'DELETE' && $id !== null) {
            $this->taskController->deleteTask($id);
            return;
        }

        $this->errorResponse('Método não permitido para essa rota.', 405);
    }

    private function getAuthorizationHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $redirected = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        return is_string($redirected) ? $redirected : null;
    }

    private function errorResponse(string $message, int $statusCode): void
    {
        http_response_code($statusCode);
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    }
}
