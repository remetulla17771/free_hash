<?php

declare(strict_types=1);

namespace app;

use app\models\User;

final class AuthService
{
    private const SESSION_KEY = '__user_id';

    private ?User $user = null;

    public function __construct(private Db $db)
    {
        $this->loadIdentity();
    }

    public function login(string $username, string $password): bool
    {
        $user = User::findByUsername($username, $this->db);

        if (!$user || !$user->validatePassword($password)) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = $user->getId();
        $this->user = $user;
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        $this->user = null;
    }

    public function identity(?string $key = null): mixed
    {
        if ($this->user === null) {
            $this->loadIdentity();
        }

        if ($key === null) {
            return $this->user;
        }

        return $this->user?->{$key};
    }

    public function isGuest(): bool
    {
        return $this->user === null;
    }

    private function loadIdentity(): void
    {
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if ($id === null) {
            return;
        }

        $this->user = User::findIdentity((int) $id, $this->db);
    }
}
