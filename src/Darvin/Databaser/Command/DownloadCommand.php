<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Command;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Download command
 */
class DownloadCommand extends Command
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('download')
            ->setDefinition([
                new InputArgument('user@host', InputArgument::REQUIRED),
                new InputArgument('port', InputArgument::OPTIONAL, '', 22),
                new InputOption('key_path', 'k', InputOption::VALUE_OPTIONAL, '', '.ssh/id_rsa'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        list($user, $host) = $this->getUserAndHost();
        $ssh = new SSH2($host, $input->getArgument('port'));

        if (!$ssh->login($user, $this->getKey())) {
            throw new \RuntimeException(sprintf('Unable to login at host "%s" as user "%s".', $host, $user));
        }

        $output->writeln($ssh->exec('pwd'));
    }

    /**
     * @return \phpseclib\Crypt\RSA
     * @throws \RuntimeException
     */
    private function getKey()
    {
        $filename = implode(DIRECTORY_SEPARATOR, [$this->detectHomeDir(), $this->input->getOption('key_path')]);

        if (!$text = @file_get_contents($filename)) {
            throw new \RuntimeException(sprintf('Unable to get key content from file "%s".', $filename));
        }

        $key = new RSA();

        if (!$key->loadKey($text)) {
            throw new \RuntimeException(sprintf('Unable to create key object from file "%s".', $filename));
        }

        return $key;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    private function detectHomeDir()
    {
        if (isset($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }
        if (isset($_SERVER['HOMEDRIVE']) && isset($_SERVER['HOMEPATH'])) {
            return $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
        }

        throw new \RuntimeException('Unable to detect home directory.');
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getUserAndHost()
    {
        $text = $this->input->getArgument('user@host');

        if (1 !== substr_count($text, '@')) {
            throw new \InvalidArgumentException(sprintf('Argument "user@host" must contain single "@" symbol, got "%s".', $text));
        }

        return explode('@', $text);
    }
}
