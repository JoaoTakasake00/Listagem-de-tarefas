<?php

declare(strict_types=1);

final class TaskService
{
    private TaskRepository $repository;
    private TaskValidator $validator;

    public function __construct(TaskRepository $repository, TaskValidator $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function listTasks(): array
    {
        return $this->repository->all();
    }

    public function createTask(array $payload): array
    {
        $task = $this->validator->validateCreatePayload($payload);
        $taskId = $this->repository->create($task);
        $createdTask = $this->repository->findById($taskId);

        if ($createdTask === null) {
            throw new RuntimeException('Falha ao recuperar tarefa criada.');
        }

        return $createdTask;
    }

    public function updateTask(int $id, array $payload): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido.');
        }

        $task = $this->validator->validateUpdatePayload($payload);
        $updated = $this->repository->update($id, $task);

        if (!$updated) {
            throw new OutOfBoundsException('Tarefa não encontrada.');
        }

        $updatedTask = $this->repository->findById($id);

        if ($updatedTask === null) {
            throw new RuntimeException('Falha ao recuperar tarefa atualizada.');
        }

        return $updatedTask;
    }

    public function deleteTask(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID inválido.');
        }

        if (!$this->repository->delete($id)) {
            throw new OutOfBoundsException('Tarefa não encontrada.');
        }
    }
}
