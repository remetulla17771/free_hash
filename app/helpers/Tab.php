<?php

namespace app\helpers;

use app\WidgetManager;

class Tab extends WidgetManager
{
    public static function widget($options)
    {

        if (empty($options['items'])) return '';

        $id = $options['id'] ?? ('tab_' . substr(md5((string)mt_rand()), 0, 8));

        $ulClass = $options['ulClass'] ?? 'nav nav-tabs';
        $contentClass = $options['contentClass'] ?? 'tab-content';

        $nav = "<ul class=\"{$ulClass}\" id=\"{$id}\" role=\"tablist\">";
        $content = "<div class=\"{$contentClass}\" id=\"{$id}_content\">";

        foreach (array_values($options['items']) as $i => $item) {
            $name = (string)($item['name'] ?? ('Tab ' . ($i + 1)));
            $tabId = $id . '_tab_' . $i;
            $paneId = $id . '_pane_' . $i;

            $active = ($i === 0);
            $btnClass = 'nav-link' . ($active ? ' active' : '');
            $paneClass = 'tab-pane fade' . ($active ? ' show active' : '');
            $dataID = $item['id'] ?? $id;


            $nav .= "<li class=\"nav-item\" role=\"presentation\">";
            $nav .= "<button data-id=\"{$dataID}\" class=\"{$btnClass}\" id=\"{$tabId}\" data-bs-toggle=\"tab\" data-bs-target=\"#{$paneId}\" type=\"button\" role=\"tab\" aria-controls=\"{$paneId}\" aria-selected=\"" . ($active ? 'true' : 'false') . "\">";
            $nav .= htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $nav .= "</button></li>";

            $paneContent = $item['content'] ?? '';
            $content .= "<div class=\"{$paneClass}\" id=\"{$paneId}\" role=\"tabpanel\" aria-labelledby=\"{$tabId}\">";
            // content можно передать строкой или callable
            if (is_callable($paneContent)) {
                $content .= (string)call_user_func($paneContent);
            } else {
                $content .= (string)$paneContent; // если нужен encode — делай htmlspecialchars тут
            }
            $content .= "</div>";
        }

        $nav .= "</ul>";
        $content .= "</div>";

        return $nav . $content;
    }
}