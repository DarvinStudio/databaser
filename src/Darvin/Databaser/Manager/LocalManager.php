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
use Ifsnop\Mysqldump\Mysqldump;

/**
 * Local manager
 */
class LocalManager implements ManagerInterface
{
    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var \Darvin\Databaser\MySql\MySqlCredentials
     */
    private $mySqlCredentials;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @param string $projectPath Project path
     */
    public function __construct($projectPath)
    {
        $this->projectPath = $projectPath;

        $this->mySqlCredentials = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbName()
    {
        return $this->getMySqlCredentials()->getDbName();
    }

    /**
     * @return LocalManager
     */
    public function clearDatabase()
    {
        $pdo = $this->getPdo();

        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $pdo->query('DROP TABLE '.$table);
        }

        $pdo->query('SET FOREIGN_KEY_CHECKS = 1');

        return $this;
    }

    /**
     * @return bool
     */
    public function databaseIsEmpty()
    {
        return 0 === $this->getPdo()->query('SHOW TABLES')->rowCount();
    }

    /**
     * @param string $filename Database dump filename
     *
     * @return LocalManager
     */
    public function dumpDatabase($filename)
    {
        $credentials = $this->getMySqlCredentials();

        (new Mysqldump($credentials->toDsn(), $credentials->getUser(), $credentials->getPassword(), [
            'compress' => Mysqldump::GZIP,
        ]))->start($filename);

        return $this;
    }

    /**
     * @return \PDO
     */
    private function getPdo()
    {
        if (empty($this->pdo)) {
            $credentials = $this->getMySqlCredentials();
            $this->pdo = new \PDO($credentials->toDsn(), $credentials->getUser(), $credentials->getPassword());
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return $this->pdo;
    }

    /**
     * @return \Darvin\Databaser\MySql\MySqlCredentials
     * @throws \RuntimeException
     */
    private function getMySqlCredentials()
    {
        if (empty($this->mySqlCredentials)) {
            $pathname = sprintf('%s/app/config/parameters.yml', $this->projectPath);
            $content = file_get_contents($pathname);

            if (false === $content) {
                throw new \RuntimeException(sprintf('Unable to get content of Symfony parameters file "%s".', $pathname));
            }

            $this->mySqlCredentials = MySqlCredentials::fromSymfonyParamsFile($content);
        }

        return $this->mySqlCredentials;
    }
}