<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017-2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Manager;

use Darvin\Databaser\MySql\MySqlCredentials;
use Darvin\Databaser\SSH\SSHClientInterface;

/**
 * Remote manager
 */
class RemoteManager extends AbstractManager implements RemoteManagerInterface
{
    /**
     * @var \Darvin\Databaser\SSH\SSHClientInterface
     */
    private $sshClient;

    /**
     * @var \Darvin\Databaser\MySql\MySqlCredentials
     */
    private $mySqlCredentials;

    /**
     * @param string                                   $projectPath Project path
     * @param \Darvin\Databaser\SSH\SSHClientInterface $sshClient   SSH client
     */
    public function __construct(string $projectPath, SSHClientInterface $sshClient)
    {
        parent::__construct($projectPath);

        $this->sshClient = $sshClient;

        $this->mySqlCredentials = null;
    }

    /**
     * {@inheritDoc}
     */
    public function createDatabase(): void
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf('mysqladmin %s create %s', $credentials->toClientArgString(false), $credentials->getDbName());

        $this->sshClient->exec($command);
    }

    /**
     * {@inheritDoc}
     */
    public function dropDatabase(): void
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf('mysqladmin %s drop %s --force', $credentials->toClientArgString(false), $credentials->getDbName());

        $this->sshClient->exec($command);
    }

    /**
     * {@inheritDoc}
     */
    public function dumpDatabase(): void
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf(
            'mysqldump %s %s | gzip -c > %s',
            $credentials->toClientArgString(false),
            $credentials->getDbName(),
            $this->getDumpPathname()
        );

        $this->sshClient->exec($command);
    }

    /**
     * {@inheritDoc}
     */
    public function downloadDump(string $localPathname): void
    {
        $this->sshClient->get($this->getDumpPathname(), $localPathname);
    }

    /**
     * {@inheritDoc}
     */
    public function importDump(string $pathname): void
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf('cat %s | gunzip | mysql %s', $pathname, $credentials->toClientArgString());

        $this->sshClient->exec($command);
    }

    /**
     * {@inheritDoc}
     */
    public function upload(string $localPathname, string $remotePathname): void
    {
        $this->sshClient->put($localPathname, $remotePathname);
    }

    /**
     * {@inheritDoc}
     */
    protected function getMySqlCredentials(): MySqlCredentials
    {
        if (null === $this->mySqlCredentials) {
            $this->mySqlCredentials = MySqlCredentials::fromSymfonyParamsFile(
                $this->sshClient->exec(sprintf('cat %s/app/config/parameters.yml', $this->projectPath))
            );
        }

        return $this->mySqlCredentials;
    }
}
