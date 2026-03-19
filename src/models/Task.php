<?php

declare(strict_types=1);

final class TaskRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(array $task): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO tasks (title, description, status, created_at) VALUES (:title, :description, :status, :created_at)'
        );

        $statement->execute([
            'title' => $task['title'],
            'description' => $task['description'],
            'status' => $task['status'],
            'created_at' => $task['created_at'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, title, description, status, created_at, updated_at FROM tasks ORDER BY created_at DESC, id DESC'
        );

        return $statement->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, title, description, status, created_at, updated_at FROM tasks WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $task = $statement->fetch();

        return $task === false ? null : $task;
    }

    public function update(int $id, array $task): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE tasks SET title = :title, description = :description, status = :status, created_at = :created_at, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'title' => $task['title'],
            'description' => $task['description'],
            'status' => $task['status'],
            'created_at' => $task['created_at'],
        ]);

        return $statement->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
