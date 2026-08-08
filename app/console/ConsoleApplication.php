<?php

declare(strict_types=1);

namespace app\console;

use app\Container;
use app\Db;

final class ConsoleApplication
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    public function __construct(private Container $container)
    {
        $config = require dirname(__DIR__) . '/config/web.php';
        $db = $this->container->has(Db::class)
            ? $this->container->get(Db::class)
            : Db::fromConfig($config['database'] ?? []);

        $this->container->singleton(Db::class, $db);
        $this->container->singleton(Container::class, $this->container);

        $this->register(new HelpCommand($this));
        $this->register($this->container->get(MakeControllerCommand::class));
        $this->register($this->container->get(MakeModelCommand::class));
        $this->register($this->container->get(MakeCrudCommand::class));
        $this->register($this->container->get(MakeMigrationCommand::class));
        $this->register($this->container->get(MigrateCommand::class));
        $this->register($this->container->get(MakeModuleCommand::class));
        $this->register($this->container->get(MakeCommand::class));

        $this->discoverGeneratedCommands();
    }

    public function register(CommandInterface $command): void
    {
        $this->commands[$command->name()] = $command;
    }

    /** @return array<string, CommandInterface> */
    public function all(): array { return $this->commands; }

    public function run(array $argv): int
    {
        $input = new Input($argv);
        $output = new Output();
        $command = $input->command();

        if ($command === null) return $this->commands['help']->execute($input, $output);
        if (!isset($this->commands[$command])) {
            $output->err("Unknown command: {$command}");
            $output->line('Run: php console help');
            return 1;
        }

        try {
            return $this->commands[$command]->execute($input, $output);
        } catch (\Throwable $e) {
            $output->err($e->getMessage());
            return 1;
        }
    }

    private function discoverGeneratedCommands(): void
    {
        foreach (glob(__DIR__ . '/*Command.php') ?: [] as $file) {
            $class = __NAMESPACE__ . '\\' . basename($file, '.php');
            if ($class === HelpCommand::class || !class_exists($class) || !is_subclass_of($class, CommandInterface::class)) continue;
            try {
                $command = $this->container->get($class);
                if ($command instanceof CommandInterface) $this->register($command);
            } catch (\Throwable) {
                // A generated command with unresolved dependencies should not prevent
                // the built-in CLI from starting.
            }
        }
    }
}
