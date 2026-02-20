<?php

namespace app\helpers;

class GridView
{
    public static function widget(array $config = []): string
    {
        if (!isset($config['dataProvider'])) {
            throw new \InvalidArgumentException('GridView: dataProvider is required');
        }

        $attributes   = $config['columns'] ?? [];
        $dataProvider = $config['dataProvider'];
        $options      = $config['options'] ?? ['class' => 'table table-bordered'];
        $pagination = $config['pagination'] ?? null;

        $optionsString = self::renderOptions($options);

        $html  = "<table{$optionsString}>";
        $html .= self::renderHeader($attributes);
        $html .= self::renderBody($attributes, $dataProvider);
        $html .= "</table>";
        if($pagination){
            $html .= LinkPager::widget(['pagination' => $config['pagination']]);
        }


        return $html;
    }

    protected static function renderHeader(array $attributes): string
    {
        $html = "<thead><tr>";

        foreach ($attributes as $attr) {
            if (is_string($attr)) {
                $label = ucfirst($attr);
            } else {
                $label = $attr['label'] ?? ucfirst($attr['attribute']);
            }

            $html .= "<th>{$label}</th>";
        }

        $html .= "</tr></thead>";
        return $html;
    }

    protected static function renderBody(array $attributes, array $dataProvider): string
    {
        $html = "<tbody>";

        foreach ($dataProvider as $model) {
            $html .= "<tr>";

            foreach ($attributes as $attr) {
                $value = '';

                if (is_string($attr)) {
                    $value = self::getValue($model, $attr);
                } elseif (is_array($attr)) {
                    if (isset($attr['value']) && is_callable($attr['value'])) {
                        $value = call_user_func($attr['value'], $model);
                    } else {
                        $value = self::getValue($model, $attr['attribute']);
                    }
                }

                $html .= "<td>{$value}</td>";
            }

            $html .= "</tr>";
        }

        $html .= "</tbody>";
        return $html;
    }

    protected static function getValue($model, string $attribute): string
    {

        if (is_array($model)) {
            $value = $model[$attribute] ?? '';
        } else {
            $value = $model->$attribute ?? '';
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    protected static function renderOptions(array $options): string
    {
        $result = '';

        foreach ($options as $key => $value) {
            $result .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }

        return $result;
    }
}
