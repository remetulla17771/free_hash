<?php
declare(strict_types=1);

namespace app\console;

interface CommandInterface
{
    public function name(): string;
    public function description(): string;
    public function execute(Input $in, Output $out): int;
}
