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
 * Local manager
 */
interface LocalManagerInterface extends ManagerInterface
{
    public function clearDatabase(): void;

    /**
     * @return bool
     */
    public function databaseIsEmpty(): bool;

    public function dumpDatabase(): void;

    /**
     * @param string $pathname Database dump pathname
     *
     * @throws \RuntimeException
     */
    public function importDump(string $pathname): void;
}
