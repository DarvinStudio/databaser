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

use Darvin\Databaser\Archiver\ArchiverInterface;
use Darvin\Databaser\MySql\MySqlCredentials;
use Ifsnop\Mysqldump\Mysqldump;

/**
 * Local manager
 */
class LocalManager extends AbstractManager implements LocalManagerInterface
{
    /**
     * @var \Darvin\Databaser\Archiver\ArchiverInterface
     */
    private $archiver;

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
     * @param string                                       $projectPath Project path
     * @param \Darvin\Databaser\Archiver\ArchiverInterface $archiver    Archiver
     */
    public function __construct(string $projectPath, ArchiverInterface $archiver)
    {
        parent::__construct($projectPath);

        $this->archiver = $archiver;

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
     * {@inheritDoc}
     */
    public function clearDatabase(): void
    {
        $pdo = $this->getPdo();

        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->beginTransaction();

        foreach ($pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN) as $table) {
            $pdo->query('DROP TABLE '.$table);
        }

        $pdo->commit();
        $pdo->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * {@inheritDoc}
     */
    public function databaseIsEmpty(): bool
    {
        return 0 === $this->getPdo()->query('SHOW TABLES')->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function dumpDatabase(): void
    {
        $credentials = $this->getMySqlCredentials();

        (new Mysqldump($credentials->toDsn(), $credentials->getUser(), $credentials->getPassword(), [
            'compress' => Mysqldump::GZIP,
        ]))->start($this->getDumpPathname());
    }

    /**
     * {@inheritDoc}
     */
    public function importDump(string $pathname): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'db_');

        if (false === $tmp) {
            throw new \RuntimeException('Unable to create temporary file.');
        }

        $this->filesToRemove[] = $tmp;

        $this->archiver->extract($pathname, $tmp);

        if (!$resource = fopen($tmp, 'r')) {
            throw new \RuntimeException(sprintf('Unable to read database dump file "%s".', $tmp));
        }

        $pdo = $this->getPdo();

        $pdo->query('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->beginTransaction();

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

        $pdo->commit();
        $pdo->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * {@inheritDoc}
     */
    protected function getMySqlCredentials(): MySqlCredentials
    {
        if (null === $this->mySqlCredentials) {
            $pathname = $this->projectPath.'.env';

            $content = @file_get_contents($pathname);

            if (false !== $content) {
                $this->mySqlCredentials = MySqlCredentials::fromSymfonyDotenvFile($content);

                return $this->mySqlCredentials;
            }

            $pathname = $this->projectPath.'app/config/parameters.yml';

            $content = @file_get_contents($pathname);

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
    private function getPdo(): \PDO
    {
        if (null === $this->pdo) {
            $credentials = $this->getMySqlCredentials();

            $this->pdo = new \PDO($credentials->toDsn(), $credentials->getUser(), $credentials->getPassword());
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->query('SET NAMES UTF8');
        }

        return $this->pdo;
    }
}
