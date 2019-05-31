<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017-2019, Darvin Studio
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
 * Push command
 */
class PushCommand extends AbstractCommand
{
    /**
     * @param string $name Command name
     */
    public function __construct(string $name = 'push')
    {
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setDescription('Pushes local database into the remote database');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $localManager = $this->createLocalManager($input);
        $remoteManager = $this->createRemoteManager($input, $output);

        $uploadPathname = $remoteManager->getProjectPath().$localManager->getDumpFilename();

        if ($localManager->databaseIsEmpty()) {
            $io->comment('Local database is empty, exiting.');

            return;
        }

        $io->comment('Dumping local database...');
        $localManager->dumpDatabase();

        $io->comment('Uploading local database dump...');
        $remoteManager->upload($localManager->getDumpPathname(), $uploadPathname);

        $io->comment('Dumping remote database...');
        $remoteManager->dumpDatabase();

        if (!$io->confirm('Remote database will be dropped. Proceed?')) {
            return;
        }

        $io->comment('Dropping remote database...');
        $remoteManager->dropDatabase();

        $io->comment('Creating remote database...');
        $remoteManager->createDatabase();

        $io->comment('Importing local database dump into the remote database...');
        $remoteManager->importDump($uploadPathname);
    }
}
