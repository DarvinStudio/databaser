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

$latest = latest();

run('git checkout master');
run('box build');

$current = str_replace('Databaser ', '', run('php databaser.phar --version')[0]);

if ($latest === $current) {
    @unlink('databaser.phar');

    error('Version has not changed');
}

$version = bump($latest);

run('git tag '.$version);
run('box build');
run('git checkout gh-pages');

$manifest = (array) json_decode(read('manifest.json'), true);

if (json_last_error()) {
    error(sprintf('Unable to decode manifest as JSON: "%s".', json_last_error_msg()));
}

$manifest[] = [
    'name'    => 'databaser.phar',
    'sha1'    => sha1(read('databaser.phar')),
    'url'     => sprintf('http://darvinstudio.github.io/databaser/downloads/databaser-%s.phar', $version),
    'version' => $version,
];

if (false === @file_put_contents('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT))) {
    error('Unable to update manifest.');
}

$target = sprintf('downloads/databaser-%s.phar', $version);

if (!rename('databaser.phar', $target)) {
    error(sprintf('Unable to move "databaser.phar" to "%s".', $target));
}

run('git add '.$target);

notify('Do not forget to push tag!');



/**
 * @param string $version Version to bump
 *
 * @return string
 */
function bump($version)
{
    return preg_replace_callback('/\d+$/', function (array $matches) { return $matches[0] + 1; }, $version);
}

/**
 * @param string $message Error message
 */
function error($message)
{
    notify($message.', exiting.');

    die;
}

/**
 * @param string $message Notification message
 */
function notify($message)
{
    echo $message.PHP_EOL;
}

/**
 * @return string
 */
function latest()
{
    $tags = run('git tag -l --sort=v:refname');

    return !empty($tags) ? array_pop($tags) : '0.0.0';
}

/**
 * @param string $pathname File pathname
 *
 * @return string
 */
function read($pathname)
{
    if (false === $content = @file_get_contents($pathname)) {
        error(sprintf('Unable to read file "%s"', $pathname));
    }

    return $content;
}

/**
 * @param string $command Command to run
 *
 * @return array
 */
function run($command)
{
    notify($command);
    exec($command, $output, $code);

    foreach ($output as $line) {
        echo $line.PHP_EOL;
    }

    echo PHP_EOL;

    if (0 !== $code) {
        die;
    }

    return $output;
}
