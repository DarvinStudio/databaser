#!/usr/bin/env php
<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

function notify($message) {
    echo $message.PHP_EOL;
}

function error($message) {
    notify($message);

    die;
}

function getLatestVersion() {
    $tags = run('git tag -l --sort=v:refname');

    return !empty($tags) ? array_pop($tags) : '0.0.0';
}

function bumpVersion($version) {
    return preg_replace_callback('/\d+$/', function (array $matches) {
        return $matches[0] + 1;
    }, $version);
}

function getFileContent($pathname) {
    if (false === $content = @file_get_contents($pathname)) {
        error(sprintf('Unable to get content of file "%s", exiting.', $pathname));
    }

    return $content;
}

function run($command) {
    notify($command);
    exec($command, $output, $exitCode);

    foreach ($output as $line) {
        echo $line.PHP_EOL;
    }

    echo PHP_EOL;

    if (0 !== $exitCode) {
        die;
    }

    return $output;
}

$latestVersion = getLatestVersion();

run('git checkout master');
run('box build');

$currentVersion = str_replace('Databaser ', '', run('php databaser.phar --version')[0]);

if ($latestVersion === $currentVersion) {
    error('Version has not changed, exiting.');
}

$version = bumpVersion($latestVersion);

run('git tag '.$version);
run('box build');
run('git checkout gh-pages');

$manifest = (array) json_decode(getFileContent('manifest.json'), true);

if (json_last_error()) {
    error(sprintf('Unable to decode manifest JSON: "%s".', json_last_error_msg()));
}

$manifest[] = [
    'name'    => 'databaser.phar',
    'sha1'    => sha1(getFileContent('databaser.phar')),
    'url'     => sprintf('http://darvinstudio.github.io/databaser/downloads/databaser-%s.phar', $version),
    'version' => $version,
];

if (false === @file_put_contents('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT))) {
    error('Unable to write new manifest file.');
}

$target = sprintf('downloads/databaser-%s.phar', $version);

if (!rename('databaser.phar', $target)) {
    error(sprintf('Unable to move "databaser.phar" to "%s".', $target));
}

run('git add '.$target);

notify('Do not forget to push tag!');
