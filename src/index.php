<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config/db.php';
require __DIR__ . '/models/Task.php';
require __DIR__ . '/models/TaskValidator.php';
require __DIR__ . '/models/TaskService.php';
require __DIR__ . '/controllers/TaskController.php';
require __DIR__ . '/monitoring/HealthService.php';
require __DIR__ . '/security/UnauthorizedException.php';
require __DIR__ . '/security/JwtService.php';
require __DIR__ . '/security/AuthService.php';
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/HealthController.php';
require __DIR__ . '/routes/api.php';

function requiredEnv(string $name): string
{
    $value = getenv($name);

    if (!is_string($value) || trim($value) === '') {
        throw new RuntimeException("Variável de ambiente obrigatória ausente: {$name}");
    }

    return trim($value);
}

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
    $pdo = dbConnection();
    $repository = new TaskRepository($pdo);
    $validator = new TaskValidator();
    $service = new TaskService($repository, $validator);
    $controller = new TaskController($service);
    $healthService = new HealthService($pdo, dirname(__DIR__));
    $jwtSecret = requiredEnv('JWT_SECRET');
    $jwtIssuer = requiredEnv('JWT_ISSUER');
    $jwtAlgorithm = requiredEnv('JWT_ALGORITHM');
    $jwtTtl = (int) (requiredEnv('JWT_EXPIRES_IN'));
    $authUser = requiredEnv('AUTH_USERNAME');
    $authPasswordHash = requiredEnv('AUTH_PASSWORD_HASH');
    $jwtService = new JwtService($jwtSecret, $jwtIssuer, $jwtTtl, $jwtAlgorithm);
    $authService = new AuthService($jwtService, $authUser, $authPasswordHash);
    $authController = new AuthController($authService);
    $healthController = new HealthController($healthService);
    $router = new ApiRouter($controller, $authController, $authService, $healthController);
    $router->dispatch($method, $path, $payload);
} catch (UnauthorizedException $exception) {
    http_response_code(401);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
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
