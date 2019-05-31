<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Manager;

/**
 * Remote manager
 */
interface RemoteManagerInterface extends ManagerInterface
{
    public function createDatabase(): void;

    public function dropDatabase(): void;

    public function dumpDatabase(): void;

    /**
     * @param string $localPathname Database dump local pathname
     */
    public function downloadDump(string $localPathname): void;

    /**
     * @param string $pathname Database dump pathname
     */
    public function importDump(string $pathname): void;

    /**
     * @param string $localPathname  File local pathname
     * @param string $remotePathname File remote pathname
     */
    public function upload(string $localPathname, string $remotePathname): void;
}
