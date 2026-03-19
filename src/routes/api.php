<?php

declare(strict_types=1);

final class ApiRouter
{
    private TaskController $taskController;

    public function __construct(TaskController $taskController)
    {
        $this->taskController = $taskController;
    }

    public function dispatch(string $method, string $uriPath, array $payload): void
    {
        $segments = explode('/', trim($uriPath, '/'));
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

    private function errorResponse(string $message, int $statusCode): void
    {
        http_response_code($statusCode);
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    }
}
