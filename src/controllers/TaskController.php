<?php

declare(strict_types=1);

final class TaskController
{
    private TaskService $service;

    public function __construct(TaskService $service)
    {
        $this->service = $service;
    }

    public function listTasks(): void
    {
        $this->jsonResponse($this->service->listTasks(), 200);
    }

    public function createTask(array $payload): void
    {
        $task = $this->service->createTask($payload);
        $this->jsonResponse($task, 201);
    }

    public function updateTask(int $id, array $payload): void
    {
        $task = $this->service->updateTask($id, $payload);
        $this->jsonResponse($task, 200);
    }

    public function deleteTask(int $id): void
    {
        $this->service->deleteTask($id);
        http_response_code(204);
    }

    private function jsonResponse(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}
