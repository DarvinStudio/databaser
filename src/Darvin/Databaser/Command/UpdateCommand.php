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
    private const MANIFEST_FILE = 'http://darvinstudio.github.io/databaser/manifest.json';

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('self-update')
            ->setDescription('Updates databaser.phar to the latest version');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $manifest = Manifest::loadFile(self::MANIFEST_FILE);
        $version = $this->getApplication()->getVersion();

        $update = $manifest->findRecent(Parser::toVersion($version), false, true);

        if (null === $update) {
            $io->comment(sprintf('You are already using latest Databaser version %s.', $version));

            return 0;
        }

        $io->comment(sprintf('Updating Databaser to version %s...', $update->getVersion()));

        (new Manager($manifest))->update($this->getApplication()->getVersion(), false, true);

        $io->success('');

        return 0;
    }
}
