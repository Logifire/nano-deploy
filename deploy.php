#!/usr/bin/env php
<?php

$script = $argv[0];

if (in_array(@$argv[1], array('--help', '-help', '-h', '-?'))) {
    $help = <<<HELP
 Usage: {$script} init <name> <repository>
 or     {$script} prepare <name> <branch or tag>
 or     {$script} activate <name> <branch or tag>
HELP;
    echo $help, PHP_EOL;
    exit(0);
}

class Deploy
{

    private const CONFIG_PATH = 'deploy-config.json';


    public function init(string $name, string $repository): void
    {

        $config = json_decode(@file_get_contents(self::CONFIG_PATH) ?: '[]', true);
        $data = json_encode(
            array_merge(
                $config,
                [$name => ['repository' => $repository]]
            ),
            JSON_PRETTY_PRINT
        );
        file_put_contents(self::CONFIG_PATH, $data);
        chmod(self::CONFIG_PATH, 0644);
    }

    // `$branch` can also be a tag
    public function prepare(string $name, string $branch): void
    {
        $directory = "{$name}-{$branch}";

        $config = $this->getConfigFor($name);
        $repository = $config['repository'];

        $specific_branch = $branch ? "--branch {$branch} --single-branch" : '';

        $command = "git clone --depth 1 {$specific_branch} -- {$repository} {$directory}";

        exec($command);
    }

    // `$branch` can also be a tag
    function activate(string $name, string $branch): void
    {
        $this->getConfigFor($name); // validate name

        $directory = "{$name}-{$branch}";

        if (is_dir($directory) === false) {
            echo "Invalid activation, run prepare first.";
            exit(1);
        }

        $command = "ln --symbolic --force --no-dereference {$directory} {$name}";

        exec($command);
    }

    private function getConfigFor(string $name): array
    {
        $config = json_decode(@file_get_contents(self::CONFIG_PATH) ?: '[]', true);

        if (!array_key_exists($name, $config)) {
            echo "Invalid name given: \"{$name}\". Hint: Run init command.", PHP_EOL;
            exit(1);
        }

        return $config[$name];
    }
}



$command = @$argv[1];
$arguments = array_slice($argv, 2);

$class = new ReflectionClass('Deploy');

if ($class->hasMethod($command) === false) {
    echo "Invalid command: \"{$command}\". \nTry '{$script} --help' for more information. \n";
    exit(1);
}


$method = $class->getMethod($command);
$number_required_params = $method->getNumberOfRequiredParameters();

if ($number_required_params > count($arguments)) {
    echo "Invalid number of arguments for \"{$command}\"; ";
    foreach ($method->getParameters() as $parameter) {
        echo "<{$parameter->getName()}> ";
    }
    echo PHP_EOL;
    exit(1);
}

(new Deploy)->$command(...$arguments);
