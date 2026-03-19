<?php

declare(strict_types=1);

final class TaskValidator
{
    private const VALID_STATUSES = ['pendente', 'em andamento', 'concluido'];

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
        $rawStatus = isset($data['status']) ? trim((string) $data['status']) : 'pendente';
        $status = $this->normalizeStatus($rawStatus);
        $createdAt = isset($data['created_at']) ? trim((string) $data['created_at']) : '';

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

        if (!$this->isValidDate($createdAt)) {
            throw new InvalidArgumentException('Data de criação inválida. Use o formato YYYY-MM-DD.');
        }

        $sanitizedTask = [
            'title' => strip_tags($title),
            'description' => strip_tags($description),
            'status' => $status,
            'created_at' => $createdAt,
        ];

        return $sanitizedTask;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = mb_strtolower(trim($status), 'UTF-8');
        $normalized = strtr($normalized, [
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'é' => 'e',
            'ê' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);

        if ($normalized === 'concluida') {
            return 'concluido';
        }

        return $normalized;
    }
}
