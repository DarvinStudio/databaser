<?php declare(strict_types=1);
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017-2019, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\MySql;

use Nyholm\DSN;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

/**
 * MySQL credentials
 */
class MySqlCredentials
{
    private const CLIENT_ARG_MAP = [
        'host'     => 'h',
        'port'     => 'P',
        'dbName'   => 'D',
        'user'     => 'u',
        'password' => 'p',
    ];

    private const DSN_PARAM_MAP = [
        'host'   => 'host',
        'port'   => 'port',
        'dbName' => 'dbname',
    ];

    private const SYMFONY_PARAM_MAP = [
        'host'     => 'database_host',
        'port'     => 'database_port',
        'dbName'   => 'database_name',
        'user'     => 'database_user',
        'password' => 'database_password',
    ];

    private const REQUIRED_SYMFONY_PARAMS = [
        'database_name',
    ];

    /**
     * @var string|null
     */
    private $host;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var string|null
     */
    private $dbName;

    /**
     * @var string|null
     */
    private $user;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @param string $content Symfony Dotenv file content
     *
     * @return MySqlCredentials
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function fromSymfonyDotenvFile(string $content): MySqlCredentials
    {
        if (!class_exists(Dotenv::class)) {
            throw new \RuntimeException('Symfony Dotenv Component is not installed.');
        }

        $params = (new Dotenv())->parse($content);

        if (!isset($params['DATABASE_URL'])) {
            throw new \InvalidArgumentException('Symfony Dotenv file is invalid: unable to find parameter "DATABASE_URL".');
        }

        $url = $params['DATABASE_URL'];

        $dsn = new DSN($url);

        if (!$dsn->isValid()) {
            throw new \InvalidArgumentException(sprintf('Unable to create MySQL credentials object from invalid database URL "%s".', $url));
        }

        $credentials = new self();

        $credentials->host     = $dsn->getFirstHost();
        $credentials->port     = $dsn->getFirstPort();
        $credentials->dbName   = $dsn->getDatabase();
        $credentials->user     = $dsn->getUsername();
        $credentials->password = $dsn->getPassword();

        return $credentials;
    }

    /**
     * @param string $content Symfony parameters file content
     *
     * @return MySqlCredentials
     * @throws \InvalidArgumentException
     */
    public static function fromSymfonyParamsFile(string $content): MySqlCredentials
    {
        $params = Yaml::parse($content);

        if (!isset($params['parameters'])) {
            throw new \InvalidArgumentException('Symfony parameters file is invalid: unable to find root element "parameters".');
        }

        $params = $params['parameters'];

        foreach (self::REQUIRED_SYMFONY_PARAMS as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to create MySQL credentials object from Symfony parameters: required parameter "%s" is missing.', $param)
                );
            }
        }

        $credentials = new self();

        foreach (self::SYMFONY_PARAM_MAP as $property => $param) {
            if (isset($params[$param])) {
                $credentials->$property = $params[$param];
            }
        }

        return $credentials;
    }

    /**
     * @param bool $includeDbName Whether to include database name
     *
     * @return string
     */
    public function toClientArgString(bool $includeDbName = true): string
    {
        $args = [];

        foreach (self::CLIENT_ARG_MAP as $property => $arg) {
            if ('dbName' === $property && !$includeDbName) {
                continue;
            }
            if (null !== $this->$property) {
                $args[] = sprintf('-%s\'%s\'', $arg, $this->$property);
            }
        }

        return implode(' ', $args);
    }

    /**
     * @return string
     */
    public function toDsn(): string
    {
        $params = [];

        foreach (self::DSN_PARAM_MAP as $property => $param) {
            if (null !== $this->$property) {
                $params[] = implode('=', [$param, $this->$property]);
            }
        }

        return 'mysql:'.implode(';', $params);
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string|null
     */
    public function getDbName(): ?string
    {
        return $this->dbName;
    }

    /**
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->user;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Constructor
     */
    private function __construct()
    {

    }
}
