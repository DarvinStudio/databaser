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

use Darvin\Databaser\Manager\LocalManager;
use Darvin\Databaser\Manager\RemoteManager;
use Darvin\Databaser\SSH\SSHClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command abstract implementation
 */
abstract class AbstractCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDefinition([
            new InputArgument('user@host', InputArgument::REQUIRED),
            new InputArgument('project_path_remote', InputArgument::REQUIRED),
            new InputArgument('project_path_local', InputArgument::OPTIONAL),
            new InputArgument('ssh_port', InputArgument::OPTIONAL, '', 22),
            new InputOption('ssh_key', 'k', InputOption::VALUE_OPTIONAL, '', '.ssh/id_rsa'),
        ]);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return \Darvin\Databaser\Manager\LocalManager
     */
    protected function createLocalManager(InputInterface $input)
    {
        return new LocalManager($input->getArgument('project_path_local'));
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return \Darvin\Databaser\Manager\RemoteManager
     */
    protected function createRemoteManager(InputInterface $input)
    {
        list($user, $host) = $this->getUserAndHost($input);

        return new RemoteManager(
            $input->getArgument('project_path_remote'),
            new SSHClient($user, $host, $input->getOption('ssh_key'), $input->getArgument('ssh_port'))
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getUserAndHost(InputInterface $input)
    {
        $text = $input->getArgument('user@host');

        if (1 !== substr_count($text, '@')) {
            throw new \InvalidArgumentException(sprintf('Argument "user@host" must contain single "@" symbol, got "%s".', $text));
        }

        return explode('@', $text);
    }
}
