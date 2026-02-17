<?php
declare(strict_types=1);

namespace app\console;

class HelpCommand implements CommandInterface
{
    public function __construct(ConsoleApplication $app) {}

    public function name(): string { return 'help'; }
    public function description(): string { return 'Show available commands'; }

    public function execute(Input $in, Output $out): int
    {
        $out->line("Commands:");
        foreach ($this->app->all() as $cmd) {
            $out->line("  - " . $cmd->name() . "  " . $cmd->description());
        }
        return 0;
    }
}
