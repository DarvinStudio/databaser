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

use Herrera\Phar\Update\Manager;
use Herrera\Phar\Update\Manifest;
use Herrera\Version\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Update command
 */
class UpdateCommand extends Command
{
    const MANIFEST_FILE = 'http://darvinstudio.github.io/databaser/manifest.json';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates databaser.phar to the latest version');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $manifest = Manifest::loadFile(self::MANIFEST_FILE);
        $version = $this->getApplication()->getVersion();

        $update = $manifest->findRecent(Parser::toVersion($version), false, true);

        if (empty($update)) {
            $io->comment(sprintf('You are already using latest Databaser version %s.', $version));

            return;
        }

        $io->comment(sprintf('Updating Databaser to version %s...', $update->getVersion()));

        (new Manager($manifest))->update($this->getApplication()->getVersion(), false, true);

        $io->success('');
    }
}
