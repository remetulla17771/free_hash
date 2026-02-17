<?php
declare(strict_types=1);

namespace app\console;

class ConsoleApplication
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function __construct()
    {
        $this->register(new HelpCommand($this));
        $this->register(new MakeControllerCommand());
        $this->register(new MakeModelCommand());
        $this->register(new MakeCrudCommand());
        $this->register(new MakeMigrationCommand());
        $this->register(new MigrateCommand());

    }

    public function register(CommandInterface $c): void
    {
        $this->commands[$c->name()] = $c;
    }

    /** @return array<string, CommandInterface> */
    public function all(): array
    {
        return $this->commands;
    }

    public function run(array $argv): int
    {
        $in = new Input($argv);
        $out = new Output();

        $cmd = $in->command();
        if ($cmd === null) return $this->commands['help']->execute($in, $out);

        if (!isset($this->commands[$cmd])) {
            $out->err("Unknown command: $cmd");
            $out->line("Run: php bin/console help");
            return 1;
        }

        return $this->commands[$cmd]->execute($in, $out);
    }
}
