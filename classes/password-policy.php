<?php

final class PasswordPolicy
{
    public const REQUIREMENTS_MESSAGE = 'Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and symbol.';

    public static function isValid(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
