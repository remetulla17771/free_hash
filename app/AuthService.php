<?php

declare(strict_types=1);

namespace app;

use app\helpers\Session;
use app\models\User;
use app\repositories\UserRepository;

final class AuthService
{
    private const SESSION_KEY = '__user_id';

    private ?User $user = null;

    public function __construct(
        private UserRepository $users,
        private Session $session,
    ) {
        $this->loadIdentity();
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);

        if (!$user || !$user->validatePassword($password)) {
            return false;
        }

        $this->session->set(self::SESSION_KEY, $user->getId());
        $this->user = $user;
        return true;
    }

    public function logout(): void
    {
        $this->session->remove(self::SESSION_KEY);
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
        return $this->identity() === null;
    }

    private function loadIdentity(): void
    {
        $id = $this->session->get(self::SESSION_KEY);
        if ($id === null) {
            return;
        }

        $this->user = $this->users->findIdentity((int) $id);

        if ($this->user === null) {
            $this->session->remove(self::SESSION_KEY);
        }
    }
}
