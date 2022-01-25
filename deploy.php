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

const CONFIG_PATH = 'deploy-config.json';

function init(string $name, string $repository): void
{

    $config = json_decode(@file_get_contents(CONFIG_PATH) ?: '[]', true);
    $data = json_encode(
        array_merge(
            $config,
            [$name => ['repository' => $repository]]
        ),
        JSON_PRETTY_PRINT
    );
    file_put_contents(CONFIG_PATH, $data);
    chmod(CONFIG_PATH, 0644);
}

// `$branch` can also be a tag
function prepare(string $name, string $branch): void
{
    $directory = "{$name}-{$branch}";

    $config = getConfigFor($name);
    $repository = $config['repository'];

    $specific_branch = $branch ? "--branch {$branch} --single-branch" : '';

    $command = "git clone --depth 1 {$specific_branch} -- {$repository} {$directory}";

    exec($command);
}

// `$branch` can also be a tag
function activate(string $name, string $branch): void
{
    getConfigFor($name); // validate name

    $directory = "{$name}-{$branch}";

    if (is_dir($directory) === false) {
        echo "Invalid activation, run prepare first.";
        exit(1);
    }

    $command = "ln --symbolic --force --no-dereference {$directory} {$name}";

    exec($command);
}

// TODO: scope this
function getConfigFor(string $name): array
{
    $config = json_decode(@file_get_contents(CONFIG_PATH) ?: '[]', true);

    if (!array_key_exists($name, $config)) {
        echo "Invalid name given: \"{$name}\". Hint: Run init command.", PHP_EOL;
        exit(1);
    }

    return $config[$name];
}

$command = @$argv[1];
$arguments = array_slice($argv, 2);

if (function_exists($command) === false) {
    echo "Invalid command: \"{$command}\". \nTry '{$script} --help' for more information. \n";
    exit(1);
}

$reflection = new ReflectionFunction($command);
$number_required_params = $reflection->getNumberOfRequiredParameters();

if ($number_required_params > count($arguments)) {
    echo "Invalid number of arguments for \"{$command}\"; ";
    foreach ($reflection->getParameters() as $parameter) {
        echo "<{$parameter->getName()}> ";
    }
    echo PHP_EOL;
    exit(1);
}

$command(...$arguments);
