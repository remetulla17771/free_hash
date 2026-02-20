<?php

namespace app\models;

use app\ActiveRecord;
use app\Auth;

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
            'token' => 'Токен'
        ];
    }

    /* ========== Identity ========== */

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public static function findByUsername(string $username)
    {
        return static::find()
            ->where(['login' => $username])
            ->one();
    }

    public function getId()
    {
        return $this->getPrimaryKey('id');
    }

    /* ========== Password ========== */

    public function validatePassword(string $password): bool
    {
        return $password === $this->password;
    }

    public function getNews()
    {
        return $this->hasMany(News::class, ['user_id' => 'id']);
    }

}
