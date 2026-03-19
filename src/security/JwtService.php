<?php

declare(strict_types=1);

final class JwtService
{
    private string $secret;
    private string $issuer;
    private int $ttlSeconds;
    private string $algorithm;

    public function __construct(string $secret, string $issuer, int $ttlSeconds, string $algorithm)
    {
        if (strtoupper($algorithm) !== 'HS256') {
            throw new RuntimeException('JWT_ALGORITHM inválido. Use HS256.');
        }

        $this->secret = $secret;
        $this->issuer = $issuer;
        $this->ttlSeconds = $ttlSeconds;
        $this->algorithm = 'HS256';
    }

    public function encode(array $payload): string
    {
        $now = time();
        $body = array_merge($payload, [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $this->ttlSeconds,
        ]);

        $header = ['alg' => $this->algorithm, 'typ' => 'JWT'];
        $headerPart = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_UNICODE));
        $payloadPart = $this->base64UrlEncode(json_encode($body, JSON_UNESCAPED_UNICODE));
        $signature = hash_hmac('sha256', "{$headerPart}.{$payloadPart}", $this->secret, true);
        $signaturePart = $this->base64UrlEncode($signature);

        return "{$headerPart}.{$payloadPart}.{$signaturePart}";
    }

    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new UnauthorizedException('Token JWT inválido.');
        }

        [$headerPart, $payloadPart, $signaturePart] = $parts;
        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', "{$headerPart}.{$payloadPart}", $this->secret, true)
        );

        if (!hash_equals($expected, $signaturePart)) {
            throw new UnauthorizedException('Assinatura do token inválida.');
        }

        $payloadJson = $this->base64UrlDecode($payloadPart);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            throw new UnauthorizedException('Payload JWT inválido.');
        }

        if (($payload['iss'] ?? '') !== $this->issuer) {
            throw new UnauthorizedException('Issuer do token inválido.');
        }

        if (!isset($payload['exp']) || (int) $payload['exp'] < time()) {
            throw new UnauthorizedException('Token expirado.');
        }

        return $payload;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        $padded = $remainder > 0 ? $value . str_repeat('=', 4 - $remainder) : $value;

        return (string) base64_decode(strtr($padded, '-_', '+/'), true);
    }
}
