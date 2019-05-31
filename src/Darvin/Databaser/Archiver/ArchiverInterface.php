<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Archiver;

/**
 * Archiver
 */
interface ArchiverInterface
{
    /**
     * @param string $source Archive file pathname
     * @param string $target Target file pathname
     *
     * @throws \RuntimeException
     */
    public function extract(string $source, string $target): void;
}
