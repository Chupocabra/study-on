<?php

namespace App\Service;

class JwtDecode
{
    public function jwtDecode($token): array
    {
        $payload = explode('.', $token)[1];
        $decode_payload = json_decode(base64_decode($payload), true);
        return [$decode_payload['exp'], $decode_payload['roles'], $decode_payload['username']];
    }
}
