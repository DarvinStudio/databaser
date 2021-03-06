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

use Darvin\Databaser\Archiver\GzipArchiver;
use Darvin\Databaser\Manager\LocalManager;
use Darvin\Databaser\Manager\LocalManagerInterface;
use Darvin\Databaser\Manager\RemoteManager;
use Darvin\Databaser\Manager\RemoteManagerInterface;
use Darvin\Databaser\SSH\SSHClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command abstract implementation
 */
abstract class AbstractCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $currentDir = $this->getCurrentDir();

        $remotePathDescription = 'Symfony project remote path absolute or relative to home directory';

        if (null !== $currentDir) {
            $remotePathDescription .= sprintf(' <comment>[default: "www/%s.%%HOST%%"]</comment>', $currentDir);
        }

        $this->setDefinition([
            new InputArgument('user@host', InputArgument::REQUIRED, 'SSH user@host'),
            new InputArgument('project_path_remote', null !== $currentDir ? InputArgument::OPTIONAL : InputArgument::REQUIRED, $remotePathDescription),
            new InputArgument(
                'project_path_local',
                InputArgument::OPTIONAL,
                'Symfony project local path absolute or relative to home directory, if empty - current directory',
                ''
            ),
            new InputOption('key', 'k', InputOption::VALUE_OPTIONAL, 'SSH private RSA key pathname relative to home directory'),
            new InputOption('password', 'p', InputOption::VALUE_NONE, 'Ask for SSH or SSH key password'),
            new InputOption('port', 'P', InputOption::VALUE_OPTIONAL, 'SSH server port'),
        ]);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return \Darvin\Databaser\Manager\LocalManagerInterface
     */
    protected function createLocalManager(InputInterface $input): LocalManagerInterface
    {
        return new LocalManager($input->getArgument('project_path_local'), new GzipArchiver());
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input  Input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     *
     * @return \Darvin\Databaser\Manager\RemoteManagerInterface
     */
    protected function createRemoteManager(InputInterface $input, OutputInterface $output): RemoteManagerInterface
    {
        list($user, $host) = $this->getUserAndHost($input);

        return new RemoteManager(
            $this->getProjectPathRemote($input, $host),
            new SSHClient(
                $user,
                $host,
                $input->getOption('key'),
                $input->getOption('password') ? (new SymfonyStyle($input, $output))->askHidden('Please enter password') : null,
                $input->getOption('port')
            )
        );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     * @param string                                          $host  Host
     *
     * @return string
     */
    private function getProjectPathRemote(InputInterface $input, string $host): string
    {
        $path = $input->getArgument('project_path_remote');

        if (null !== $path) {
            return $path;
        }

        return implode('.', [$this->getCurrentDir(), $host]);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input Input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    private function getUserAndHost(InputInterface $input): array
    {
        $text = $input->getArgument('user@host');

        if (1 !== substr_count($text, '@')) {
            return ['www-data', $text];
        }

        return explode('@', $text);
    }

    /**
     * @return string|null
     */
    private function getCurrentDir(): ?string
    {
        $cwd = getcwd();

        if (false === $cwd) {
            return null;
        }

        $parts = explode(DIRECTORY_SEPARATOR, $cwd);

        return array_pop($parts);
    }
}
