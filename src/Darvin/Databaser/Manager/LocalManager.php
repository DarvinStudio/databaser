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

use Darvin\Databaser\Archiver\GzipArchiver;
use Darvin\Databaser\MySql\MySqlCredentials;
use Ifsnop\Mysqldump\Mysqldump;

/**
 * Local manager
 */
class LocalManager extends AbstractManager
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
     * @var string[]
     */
    private $filesToRemove;

    /**
     * @param string $projectPath Project path
     */
    public function __construct($projectPath)
    {
        $this->projectPath = $projectPath;

        $this->mySqlCredentials = $this->pdo = null;
        $this->filesToRemove = [];
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        foreach ($this->filesToRemove as $pathname) {
            @unlink($pathname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectPath()
    {
        return $this->projectPath;
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
     * @return LocalManager
     */
    public function dumpDatabase()
    {
        $credentials = $this->getMySqlCredentials();

        (new Mysqldump($credentials->toDsn(), $credentials->getUser(), $credentials->getPassword(), [
            'compress' => Mysqldump::GZIP,
        ]))->start($this->getDumpPathname());

        return $this;
    }

    /**
     * @param string $filename Database dump filename
     *
     * @return LocalManager
     * @throws \RuntimeException
     */
    public function importDump($filename)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'db_');

        if (false === $tmp) {
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $this->filesToRemove[] = $tmp;

        (new GzipArchiver())->extract($filename, $tmp);

        if (!$resource = fopen($tmp, 'r')) {
            throw new \RuntimeException(sprintf('Unable to read database dump file "%s".', $tmp));
        }

        $pdo = $this->getPdo();

        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');

        $query = '';

        while ($line = fgets($resource)) {
            if (0 === strpos($line, '/*') || 0 === strpos($line, '--')) {
                continue;
            }

            $line = trim($line);

            $query .= $line;

            if (preg_match('/;$/', $line)) {
                $pdo->query($query);

                $query = '';
            }
        }

        $pdo->query('SET FOREIGN_KEY_CHECKS = 1');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMySqlCredentials()
    {
        if (empty($this->mySqlCredentials)) {
            $pathname = 'app/config/parameters.yml';

            if (!empty($this->projectPath)) {
                $pathname = preg_replace('/\/*$/', '/', $this->projectPath).$pathname;
            }

            $content = file_get_contents($pathname);

            if (false === $content) {
                throw new \RuntimeException(sprintf('Unable to get content of Symfony parameters file "%s".', $pathname));
            }

            $this->mySqlCredentials = MySqlCredentials::fromSymfonyParamsFile($content);
        }

        return $this->mySqlCredentials;
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
            $this->pdo->query('SET NAMES UTF8');
        }

        return $this->pdo;
    }
}
