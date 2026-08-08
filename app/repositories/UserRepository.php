<?php

declare(strict_types=1);

namespace app\repositories;

use app\Db;
use app\models\User;

final class UserRepository
{
    public function __construct(private Db $db)
    {
    }

    public function findIdentity(int $id): ?User
    {
        return User::findOne($id, $this->db);
    }

    public function findByUsername(string $username): ?User
    {
        return User::find($this->db)
            ->where(['login' => $username])
            ->one();
    }
}
