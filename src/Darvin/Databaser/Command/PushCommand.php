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
 * Push command
 */
class PushCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('push');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $remoteManager = $this->createRemoteManager($input);
        $localManager = $this->createLocalManager($input);

        $io->comment('Dumping local database...');

        $localManager->dumpDatabase();

        $uploadPathname = $remoteManager->getProjectPath().$localManager->getDumpFilename();

        $io->comment('Uploading local database dump...');

        $remoteManager->upload($localManager->getDumpPathname(), $uploadPathname);

        $io->comment('Dumping remote database...');

        $remoteManager->dumpDatabase();
    }
}
