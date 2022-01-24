#!/usr/bin/env php
<?php

if ($argc > 3 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    echo "Usage: {$argv[0]} <repository> [branch]" . PHP_EOL;
    exit(0);
}

$repository = $argv[1];
$branch = @$argv[2];



$specific_branch = $branch ? "--branch {$branch} --single-branch" : '';

$command = "git clone --depth 1 {$repository} {$specific_branch}";

`$command`;
