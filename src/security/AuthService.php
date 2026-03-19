<?php

declare(strict_types=1);

final class AuthService
{
    private JwtService $jwtService;
    private string $username;
    private string $passwordHash;

    public function __construct(JwtService $jwtService, string $username, string $passwordHash)
    {
        if (($passwordHash === '') || (password_get_info($passwordHash)['algo'] === null)) {
            throw new RuntimeException('Hash de senha de autenticação inválido.');
        }

        $this->jwtService = $jwtService;
        $this->username = $username;
        $this->passwordHash = $passwordHash;
    }

    public function login(array $payload): array
    {
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException('Usuário e senha são obrigatórios.');
        }

        if (!hash_equals($this->username, $username) || !password_verify($password, $this->passwordHash)) {
            throw new UnauthorizedException('Credenciais inválidas.');
        }

        $token = $this->jwtService->encode([
            'sub' => $username,
            'role' => 'admin',
        ]);

        return [
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => [
                'username' => $username,
            ],
        ];
    }

    public function requireValidToken(?string $authorizationHeader): array
    {
        if ($authorizationHeader === null || trim($authorizationHeader) === '') {
            throw new UnauthorizedException('Token de autenticação não informado.');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            throw new UnauthorizedException('Formato do header Authorization inválido.');
        }

        $token = trim($matches[1]);
        if ($token === '') {
            throw new UnauthorizedException('Token JWT vazio.');
        }

        return $this->jwtService->decode($token);
    }
}
