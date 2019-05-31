<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017-2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\SSH;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;

/**
 * SSH client
 */
class SSHClient
{
    /**
     * @var \phpseclib\Net\SSH2
     */
    private $session;

    /**
     * @var \phpseclib\Net\SCP
     */
    private $scp;

    /**
     * @param string $user        Username
     * @param string $host        Hostname
     * @param string $keyPathname Private key file pathname relative to home directory
     * @param string $password    Password
     * @param int    $port        Port
     *
     * @throws \RuntimeException
     */
    public function __construct(string $user, string $host, string $keyPathname, string $password, int $port)
    {
        $this->session = new SSH2($host, $port);
        $this->session->enableQuietMode();

        $key = $this->getKey($keyPathname, $password);

        if (!$this->session->login($user, !empty($key) ? $key : $password)) {
            throw new \RuntimeException(sprintf('Unable to login at host "%s" as user "%s".', $host, $user));
        }

        $this->scp = null;
    }

    /**
     * @param string $command Command
     *
     * @return string
     * @throws \RuntimeException
     */
    public function exec(string $command): string
    {
        $output = $this->session->exec($command);

        if (0 !== $this->session->getExitStatus()) {
            throw new \RuntimeException($this->session->getStdError());
        }

        return trim($output);
    }

    /**
     * @param string $remotePathname File remote pathname
     * @param string $localPathname  File local pathname
     *
     * @return SSHClient
     * @throws \RuntimeException
     */
    public function get(string $remotePathname, string $localPathname): SSHClient
    {
        if (!$this->getScp()->get($remotePathname, $localPathname)) {
            throw new \RuntimeException(sprintf('Unable to get file "%s".', $remotePathname));
        }

        return $this;
    }

    /**
     * @param string $localPathname  File local pathname
     * @param string $remotePathname File remote pathname
     *
     * @return SSHClient
     * @throws \RuntimeException
     */
    public function put(string $localPathname, string $remotePathname): SSHClient
    {
        if (!$this->getScp()->put($remotePathname, $localPathname, SCP::SOURCE_LOCAL_FILE)) {
            throw new \RuntimeException(sprintf('Unable to put file "%s".', $remotePathname));
        }

        return $this;
    }

    /**
     * @param string $pathname Private key file pathname relative to home directory
     * @param string $password Password
     *
     * @return \phpseclib\Crypt\RSA
     * @throws \RuntimeException
     */
    private function getKey(string $pathname, string $password): RSA
    {
        $filename = implode(DIRECTORY_SEPARATOR, [$this->detectHomeDir(), $pathname]);

        if (!$text = @file_get_contents($filename)) {
            return null;
        }

        $key = new RSA();

        if (!$key->loadKey($text)) {
            throw new \RuntimeException(sprintf('Unable to create key object from file "%s".', $filename));
        }
        if (!empty($password)) {
            $key->setPassword($password);
        }

        return $key;
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    private function detectHomeDir(): string
    {
        if (isset($_SERVER['HOME'])) {
            return $_SERVER['HOME'];
        }
        if (isset($_SERVER['HOMEDRIVE']) && isset($_SERVER['HOMEPATH'])) {
            return $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
        }

        throw new \RuntimeException('Unable to detect home directory.');
    }

    /**
     * @return \phpseclib\Net\SCP
     */
    private function getScp(): SCP
    {
        if (null === $this->scp) {
            $this->scp = new SCP($this->session);
        }

        return $this->scp;
    }
}
