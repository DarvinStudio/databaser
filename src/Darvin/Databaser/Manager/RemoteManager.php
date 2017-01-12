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
class RemoteManager extends AbstractManager
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
     * {@inheritdoc}
     */
    public function getProjectPath()
    {
        return $this->projectPath;
    }

    /**
     * @return RemoteManager
     */
    public function dumpDatabase()
    {
        $credentials = $this->getMySqlCredentials();

        $command = sprintf(
            'mysqldump %s %s | gzip -c > %s',
            $credentials->toClientArgString(false),
            $credentials->getDbName(),
            $this->getDumpPathname()
        );

        $this->sshClient->exec($command);

        return $this;
    }

    /**
     * @param string $localPathname Database dump local pathname
     *
     * @return RemoteManager
     */
    public function downloadDump($localPathname)
    {
        $this->sshClient->get($this->getDumpPathname(), $localPathname);

        return $this;
    }

    /**
     * @param string $localPathname  File local pathname
     * @param string $remotePathname File remote pathname
     *
     * @return RemoteManager
     */
    public function upload($localPathname, $remotePathname)
    {
        $this->sshClient->put($localPathname, $remotePathname);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMySqlCredentials()
    {
        if (empty($this->mySqlCredentials)) {
            $this->mySqlCredentials = MySqlCredentials::fromSymfonyParamsFile(
                $this->sshClient->exec(sprintf('cat %s/app/config/parameters.yml', $this->projectPath))
            );
        }

        return $this->mySqlCredentials;
    }
}
