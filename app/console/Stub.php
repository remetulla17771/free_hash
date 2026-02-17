<?php
declare(strict_types=1);

namespace app\console;

class Stub
{
    public static function render(string $templatePath, array $vars): string
    {
        $tpl = file_get_contents($templatePath);
        if ($tpl === false) throw new \RuntimeException("Stub not found: $templatePath");

        foreach ($vars as $k => $v) {
            $tpl = str_replace('{{' . $k . '}}', (string)$v, $tpl);
        }
        return $tpl;
    }
}
