<?php

namespace App\DTO;

readonly class LoginUserDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
