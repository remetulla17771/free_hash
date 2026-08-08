<?php

declare(strict_types=1);

namespace app\models;

use app\ActiveRecord;
use app\Auth;
use app\Db;

class User extends ActiveRecord implements Auth
{
    public static function tableName(): string
    {
        return 'user';
    }

    public function attributeLabels()
    {
        return [
            'login' => 'Логин',
            'password' => 'Пароль',
            'token' => 'Токен',
        ];
    }

    public static function findIdentity(int $id, Db $db): ?static
    {
        return static::findOne($id, $db);
    }

    public static function findByUsername(string $username, Db $db): ?static
    {
        return static::find($db)
            ->where(['login' => $username])
            ->one();
    }

    public function getId(): mixed
    {
        return $this->getPrimaryKey('id');
    }

    public function validatePassword(string $password): bool
    {
        return $password === $this->password;
    }

    public function getNews()
    {
        return $this->hasMany(News::class, ['user_id' => 'id']);
    }
}
