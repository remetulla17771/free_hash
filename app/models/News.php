<?php
declare(strict_types=1);

namespace app\models;

use app\ActiveRecord;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $title
 * @property string|null $content
 */
class News extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'news';
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'Id',
            'user_id' => 'Пользователь',
            'title' => 'Title',
            'content' => 'Content',
        ];
    }
}
