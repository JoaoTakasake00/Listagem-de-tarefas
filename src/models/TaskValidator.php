<?php

declare(strict_types=1);

final class TaskValidator
{
    private const VALID_STATUSES = ['pendente', 'em andamento', 'concluída'];

    public function validateCreatePayload(array $data): array
    {
        return $this->validateTaskPayload($data);
    }

    public function validateUpdatePayload(array $data): array
    {
        return $this->validateTaskPayload($data);
    }

    private function validateTaskPayload(array $data): array
    {
        $title = isset($data['title']) ? trim((string) $data['title']) : '';
        $description = isset($data['description']) ? trim((string) $data['description']) : '';
        $status = isset($data['status']) ? trim((string) $data['status']) : 'pendente';

        if ($title === '') {
            throw new InvalidArgumentException('Título é obrigatório.');
        }

        if (strlen($title) > 120) {
            throw new InvalidArgumentException('Título deve ter no máximo 120 caracteres.');
        }

        if (strlen($description) > 1000) {
            throw new InvalidArgumentException('Descrição deve ter no máximo 1000 caracteres.');
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        $sanitizedTask = [
            'title' => strip_tags($title),
            'description' => strip_tags($description),
            'status' => $status,
        ];

        return $sanitizedTask;
    }
}
