<?php
declare(strict_types=1);

namespace app\models;

use app\ActiveRecord;

/**
 * @property int $id
 * @property string|null $title_ru
 * @property string|null $title_kk
 * @property string|null $content_ru
 * @property string|null $content_kk
 */
class Podman extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'podman';
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'Id',
            'title_ru' => 'Title Ru',
            'title_kk' => 'Title Kk',
            'content_ru' => 'Content Ru',
            'content_kk' => 'Content Kk',
        ];
    }
}
