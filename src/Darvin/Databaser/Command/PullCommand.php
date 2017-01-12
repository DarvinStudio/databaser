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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Pull command
 */
class PullCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('pull');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $remoteManager = $this->createRemoteManager($input);

        $io->comment('Dumping remote database...');

        $remoteManager->dumpDatabase();

        $localManager = $this->createLocalManager($input);

        $downloadPathname = $remoteManager->getDumpFilename();
        $projectPathLocal = $localManager->getProjectPath();

        if (!empty($projectPathLocal)) {
            $downloadPathname = preg_replace('/\/*$/', '/', $projectPathLocal).$downloadPathname;
        }

        $io->comment('Downloading remote database dump...');

        $remoteManager->downloadDump($downloadPathname);

        if (!$localManager->databaseIsEmpty()) {
            $io->comment('Dumping local database...');

            $localManager->dumpDatabase();

            $io->comment('Clearing local database...');

            $localManager->clearDatabase();
        }

        $io->comment('Importing remote database dump into the local database...');

        $localManager->importDump($downloadPathname);
    }
}
