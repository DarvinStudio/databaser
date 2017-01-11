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

use Darvin\Databaser\SSH\SSHClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

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
                new InputArgument('project_path', InputArgument::REQUIRED),
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

        $ssh = new SSHClient($user, $host, $input->getOption('key_path'), $input->getArgument('port'));

        $projectPath = $input->getArgument('project_path');

        $params = Yaml::parse($ssh->exec(sprintf('cat %s/app/config/parameters.yml', $projectPath)));
        $params = $this->getParameter($params, 'parameters');

        $dbName = $this->getParameter($params, 'database_name');

        $args = [];

        foreach ([
            'h' => 'database_host',
            'P' => 'database_port',
            'u' => 'database_user',
            'p' => 'database_password',
        ] as $arg => $param) {
            $value = $this->getParameter($params, $param, false);

            if (null !== $value) {
                $args[] = '-'.$arg.$value;
            }
        }

        $dumpFilename = sprintf('%s_%s.sql.bz2', $dbName, (new \DateTime())->format('d-m-Y_H-i-s'));
        $dumpPathname = implode(DIRECTORY_SEPARATOR, [$projectPath, $dumpFilename]);
        $command = sprintf('mysqldump %s %s | bzip2 -c > %s', implode(' ', $args), $dbName, $dumpPathname);
        $ssh->exec($command);
        $ssh->download($dumpPathname, $dumpFilename);
    }

    /**
     * @param array  $params            Parameters
     * @param string $name              Element name
     * @param bool   $notFoundException Whether to throw not found exception
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getParameter(array $params, $name, $notFoundException = true)
    {
        if (isset($params[$name])) {
            return $params[$name];
        }
        if ($notFoundException) {
            throw new \RuntimeException(sprintf('Parameters file is invalid: unable to find "%s" element.', $name));
        }

        return null;
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
