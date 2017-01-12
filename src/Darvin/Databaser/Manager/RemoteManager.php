<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Manager;

use Darvin\Databaser\MySql\MySqlCredentials;
use Darvin\Databaser\SSH\SSHClient;

/**
 * Remote manager
 */
class RemoteManager
{
    /**
     * @var \Darvin\Databaser\SSH\SSHClient
     */
    private $sshClient;

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var \Darvin\Databaser\MySql\MySqlCredentials
     */
    private $mySqlCredentials;

    /**
     * @param \Darvin\Databaser\SSH\SSHClient $sshClient   SSH client
     * @param string                          $projectPath Project path
     */
    public function __construct(SSHClient $sshClient, $projectPath)
    {
        $this->sshClient = $sshClient;
        $this->projectPath = $projectPath;

        $this->mySqlCredentials = null;
    }

    /**
     * @param string $pathname Database dump pathname
     *
     * @return RemoteManager
     */
    public function dumpDatabase($pathname)
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf('mysqldump %s %s | gzip -c > %s', $credentials->toClientArgString(false), $credentials->getDbName(), $pathname);

        $this->sshClient->exec($command);

        return $this;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->getMySqlCredentials()->getDbName();
    }

    /**
     * @param string $remotePathname Remote file pathname
     * @param string $localPathname  Local file pathname
     *
     * @return RemoteManager
     */
    public function getFile($remotePathname, $localPathname)
    {
        $this->sshClient->getFile($remotePathname, $localPathname);

        return $this;
    }

    /**
     * @return \Darvin\Databaser\MySql\MySqlCredentials
     * @throws \RuntimeException
     */
    public function getMySqlCredentials()
    {
        if (empty($this->mySqlCredentials)) {
            $this->mySqlCredentials = MySqlCredentials::fromSymfonyParamsFile(
                $this->sshClient->exec(sprintf('cat %s/app/config/parameters.yml', $this->projectPath))
            );
        }

        return $this->mySqlCredentials;
    }
}
