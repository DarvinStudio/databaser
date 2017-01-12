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

/**
 * Manager abstract implementation
 */
abstract class AbstractManager
{
    /**
     * @var string
     */
    protected $dumpFilename;

    /**
     * @var string
     */
    protected $dumpPathname;

    /**
     * @return string
     */
    public function getDumpPathname()
    {
        if (empty($this->dumpPathname)) {
            $pathname = $this->getDumpFilename();

            $projectPath = $this->getProjectPath();

            if (!empty($projectPath)) {
                $pathname = preg_replace('/\/*$/', '/', $projectPath).$pathname;
            }

            $this->dumpPathname = $pathname;
        }

        return $this->dumpPathname;
    }

    /**
     * @return string
     */
    public function getDumpFilename()
    {
        if (empty($this->dumpFilename)) {
            $this->dumpFilename = sprintf(
                '%s_%s.sql.gz',
                $this->getMySqlCredentials()->getDbName(),
                (new \DateTime())->format('d-m-Y_H-i-s')
            );
        }

        return $this->dumpFilename;
    }

    /**
     * @return string
     */
    abstract public function getProjectPath();

    /**
     * @return \Darvin\Databaser\MySql\MySqlCredentials
     * @throws \RuntimeException
     */
    abstract protected function getMySqlCredentials();
}
