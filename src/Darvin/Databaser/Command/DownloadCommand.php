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

use Darvin\Databaser\Archiver\GzipArchiver;
use Darvin\Databaser\Manager\RemoteManager;
use Darvin\Databaser\SSH\SSHClient;
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('download')
            ->setDefinition([
                new InputArgument('user@host', InputArgument::REQUIRED),
                new InputArgument('remote_project_path', InputArgument::REQUIRED),
                new InputArgument('port', InputArgument::OPTIONAL, '', 22),
                new InputOption('key_path', 'k', InputOption::VALUE_OPTIONAL, '', '.ssh/id_rsa'),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($user, $host) = $this->getUserAndHost($input);

        $remoteProjectPath = $input->getArgument('remote_project_path');

        $remoteManager = new RemoteManager(
            new SSHClient($user, $host, $input->getOption('key_path'), $input->getArgument('port')),
            $remoteProjectPath
        );

        $filename = sprintf('%s_%s.sql', $remoteManager->getDbName(), (new \DateTime())->format('d-m-Y_H-i-s'));
        $archiveFilename = $filename.'.gz';
        $archivePathname = implode(DIRECTORY_SEPARATOR, [$remoteProjectPath, $archiveFilename]);

        $remoteManager->dumpDatabase($archivePathname)->getFile($archivePathname, $archiveFilename);

        (new GzipArchiver())->extract($archiveFilename, $filename);
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
