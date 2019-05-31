<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\SSH;

/**
 * SSH client
 */
interface SSHClientInterface
{
    /**
     * @param string $command Command
     *
     * @return string
     * @throws \RuntimeException
     */
    public function exec(string $command): string;

    /**
     * @param string $remotePathname File remote pathname
     * @param string $localPathname  File local pathname
     *
     * @return SSHClientInterface
     * @throws \RuntimeException
     */
    public function get(string $remotePathname, string $localPathname): SSHClientInterface;

    /**
     * @param string $localPathname  File local pathname
     * @param string $remotePathname File remote pathname
     *
     * @return SSHClientInterface
     * @throws \RuntimeException
     */
    public function put(string $localPathname, string $remotePathname): SSHClientInterface;
}
