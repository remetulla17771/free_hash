<?php
declare(strict_types=1);

namespace app\console;

class Output
{
    public function line(string $s=''): void { echo $s . PHP_EOL; }
    public function err(string $s=''): void { fwrite(STDERR, $s . PHP_EOL); }
}
