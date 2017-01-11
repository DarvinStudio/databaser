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
     * @var \Darvin\Databaser\MySQL\MySQLCredentials
     */
    private $mySQLCredentials;

    /**
     * @param \Darvin\Databaser\SSH\SSHClient $sshClient SSH client
     */
    public function __construct(SSHClient $sshClient)
    {
        $this->sshClient = $sshClient;

        $this->mySQLCredentials = null;
    }
}
