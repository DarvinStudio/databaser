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
use Darvin\Databaser\Manager\ManagerInterface;
use Darvin\Databaser\Manager\RemoteManager;
use Darvin\Databaser\SSH\SSHClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
                new InputArgument('ssh_user', InputArgument::REQUIRED),
                new InputArgument('ssh_host', InputArgument::REQUIRED),
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
        $io = new SymfonyStyle($input, $output);

        $projectPathRemote = $input->getArgument('project_path_remote');

        $remoteManager = new RemoteManager(
            new SSHClient(
                $input->getArgument('ssh_user'),
                $input->getArgument('ssh_host'),
                $input->getOption('ssh_key'),
                $input->getArgument('ssh_port')
            ),
            $projectPathRemote
        );

        $filenameRemote = $this->createDumpFilename($remoteManager);
        $pathnameRemote = implode(DIRECTORY_SEPARATOR, [$projectPathRemote, $filenameRemote]);

        $io->comment('Dumping remote database...');

        $remoteManager->dumpDatabase($pathnameRemote);

        $io->comment('Downloading remote database dump...');

        $remoteManager->getFile($pathnameRemote, $filenameRemote);

        $localManager = new LocalManager($input->getArgument('project_path_local'));

        if (!$localManager->databaseIsEmpty()) {
            $io->comment('Dumping local database...');

            $localManager->dumpDatabase($this->createDumpFilename($localManager));

            $io->comment('Dropping local database...');

            $localManager->clearDatabase();
        }
    }

    /**
     * @param \Darvin\Databaser\Manager\ManagerInterface $manager Manager
     *
     * @return string
     */
    private function createDumpFilename(ManagerInterface $manager)
    {
        return sprintf('%s_%s.sql.gz', $manager->getDbName(), (new \DateTime())->format('d-m-Y_H-i-s'));
    }
}
