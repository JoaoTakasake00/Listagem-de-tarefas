<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Task.php';
require __DIR__ . '/models/TaskValidator.php';
require __DIR__ . '/models/TaskService.php';
require __DIR__ . '/controllers/TaskController.php';
require __DIR__ . '/routes/api.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $repository = new TaskRepository(dbConnection());
    $validator = new TaskValidator();
    $service = new TaskService($repository, $validator);
    $controller = new TaskController($service);
    $router = new ApiRouter($controller);
    $router->dispatch($method, $path, $payload);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (OutOfBoundsException $exception) {
    http_response_code(404);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno ao processar a requisição.'], JSON_UNESCAPED_UNICODE);
}
