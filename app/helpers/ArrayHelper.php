<?php

namespace app\helpers;

class ArrayHelper
{
    /**
     * Получить значение по ключу с поддержкой dot-нотации
     * ArrayHelper::getValue($arr, 'user.profile.name')
     */
    public static function getValue(array $array, $key, $default = null)
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (is_string($key) && str_contains($key, '.')) {
            foreach (explode('.', $key) as $part) {
                if (is_array($array) && array_key_exists($part, $array)) {
                    $array = $array[$part];
                } else {
                    return $default;
                }
            }
            return $array;
        }

        return $default;
    }

    /**
     * Вытянуть колонку из массива объектов или массивов
     */
    public static function getColumn(array $array, string $name): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && isset($item[$name])) {
                $result[] = $item[$name];
            } elseif (is_object($item) && isset($item->$name)) {
                $result[] = $item->$name;
            }
        }

        return $result;
    }

    /**
     * Преобразовать массив в map: key => value
     */
    public static function map(
        array $array,
        string $from,
        string $to,
        ?string $group = null
    ): array {
        $result = [];

        foreach ($array as $item) {
            $key   = is_array($item) ? $item[$from] ?? null : $item->$from ?? null;
            $value = is_array($item) ? $item[$to]   ?? null : $item->$to   ?? null;

            if ($key === null) {
                continue;
            }

            if ($group !== null) {
                $groupKey = is_array($item)
                    ? ($item[$group] ?? null)
                    : ($item->$group ?? null);

                $result[$groupKey][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Проверка: массив ассоциативный?
     */
    public static function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Убрать null значения
     */
    public static function filter(array $array): array
    {
        return array_filter($array, static fn($v) => $v !== null);
    }


    public static function convertObjectToArray($model, $name, $content){
        $a = "app\models\\$model";
        $m = $model::find()->all();
        $arr = [];

        foreach ($m as $m){
            $arr[$m->id]['name'] = $m->$name;
            $arr[$m->id]['content'] = $m->$content;
        }

        return $arr;
    }


}
