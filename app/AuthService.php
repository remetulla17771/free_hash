<?php


namespace app;

use app\models\User;

class AuthService
{
    private const SESSION_KEY = '__user_id';
    public $user;

    public function __construct()
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        $this->user = User::findIdentity($_SESSION[self::SESSION_KEY]);
    }

    public static function login(string $username, string $password): bool
    {
        $user = User::findByUsername($username);

        if (!$user) {
            return false;
        }

        if (!$user->validatePassword($password)) {
            return false;
        }

        $_SESSION[self::SESSION_KEY] = $user->getId();
        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function identity($key = null)
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        static $identity = null;

        if ($identity === null) {
            $identity = User::findIdentity($_SESSION[self::SESSION_KEY]);
        }

        if (!$identity) {
            return null;
        }

        if ($key === null) {
            return $identity;
        }

        // 👇 ВАЖНО: ActiveRecord + __get()
        return $identity->$key ?? null;
    }



    public static function isGuest(): bool
    {
        return self::identity() === null;
    }
}
