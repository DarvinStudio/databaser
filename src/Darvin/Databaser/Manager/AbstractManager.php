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

/**
 * Manager abstract implementation
 */
abstract class AbstractManager implements ManagerInterface
{
    /**
     * @var string
     */
    protected $projectPath;

    /**
     * @var string
     */
    protected $dumpFilename;

    /**
     * @var string
     */
    protected $dumpPathname;

    /**
     * @param string $projectPath Project path
     */
    public function __construct(string $projectPath)
    {
        if ('' !== $projectPath) {
            $projectPath = preg_replace('/\/*$/', '/', $projectPath);
        }

        $this->projectPath = $projectPath;

        $this->dumpFilename = $this->dumpPathname = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getDumpPathname(): string
    {
        if (null === $this->dumpPathname) {
            $this->dumpPathname = $this->projectPath.$this->getDumpFilename();
        }

        return $this->dumpPathname;
    }

    /**
     * {@inheritDoc}
     */
    public function getDumpFilename(): string
    {
        if (null === $this->dumpFilename) {
            $this->dumpFilename = sprintf(
                '%s_%s.sql.gz',
                $this->getMySqlCredentials()->getDbName(),
                (new \DateTime())->format('Y-m-d_H-i')
            );
        }

        return $this->dumpFilename;
    }

    /**
     * {@inheritDoc}
     */
    public function getProjectPath(): string
    {
        return $this->projectPath;
    }

    /**
     * @return \Darvin\Databaser\MySql\MySqlCredentials
     * @throws \RuntimeException
     */
    abstract protected function getMySqlCredentials(): MySqlCredentials;
}
