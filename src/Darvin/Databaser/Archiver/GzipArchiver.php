<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017-2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\Archiver;

/**
 * GZIP archiver
 */
class GzipArchiver
{
    /**
     * @var int
     */
    private $bufferSize;

    /**
     * @param int $bufferSize Buffer size
     */
    public function __construct(int $bufferSize = 4096)
    {
        $this->bufferSize = $bufferSize;
    }

    /**
     * @param string $source Archive file pathname
     * @param string $target Target file pathname
     *
     * @throws \RuntimeException
     */
    public function extract(string $source, string $target): void
    {
        if (!$sourceRes = gzopen($source, 'r')) {
            throw new \RuntimeException(sprintf('Unable to read archive file "%s".', $source));
        }
        if (!$targetRes = fopen($target, 'w')) {
            throw new \RuntimeException(sprintf('Unable to write target file "%s".', $target));
        }
        while (!gzeof($sourceRes)) {
            fwrite($targetRes, gzread($sourceRes, $this->bufferSize));
        }
        if (!gzclose($sourceRes)) {
            throw new \RuntimeException(sprintf('Unable to close archive file "%s".', $source));
        }
        if (!fclose($targetRes)) {
            throw new \RuntimeException(sprintf('Unable to close target file "%s".', $target));
        }
    }
}
