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

use Darvin\Databaser\Manager\RemoteManager;
use Darvin\Databaser\SSH\SSHClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Pull command
 */
class PullCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('pull')
            ->setDefinition([
                new InputArgument('user@host', InputArgument::REQUIRED),
                new InputArgument('project_path_remote', InputArgument::REQUIRED),
                new InputArgument('project_path_local', InputArgument::REQUIRED),
                new InputArgument('ssh_port', InputArgument::OPTIONAL, '', 22),
                new InputOption('ssh_key', 'k', InputOption::VALUE_OPTIONAL, '', '.ssh/id_rsa'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($user, $host) = $this->getUserAndHost($input);

        $projectPathRemote = $input->getArgument('project_path_remote');

        $remoteManager = new RemoteManager(
            new SSHClient($user, $host, $input->getOption('ssh_key'), $input->getArgument('ssh_port')),
            $projectPathRemote
        );

        $filename = sprintf('%s_%s.sql.gz', $remoteManager->getDbName(), (new \DateTime())->format('d-m-Y_H-i-s'));
        $pathname = implode(DIRECTORY_SEPARATOR, [$projectPathRemote, $filename]);

        $remoteManager
            ->dumpDatabase($pathname)
            ->getFile($pathname, $filename);
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
