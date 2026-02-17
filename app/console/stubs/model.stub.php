<?php
declare(strict_types=1);

namespace {{namespace}};

use app\ActiveRecord;

{{phpdoc}}
class {{class}} extends ActiveRecord
{
    public static function tableName(): string
{
    return '{{table}}';
}
}
