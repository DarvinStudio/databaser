<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2017, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\Databaser\MySql;

use Symfony\Component\Yaml\Yaml;

/**
 * MySQL credentials
 */
class MySqlCredentials
{
    /**
     * @var array
     */
    private static $clientArgMap = [
        'host'     => 'h',
        'port'     => 'P',
        'dbName'   => 'D',
        'user'     => 'u',
        'password' => 'p',
    ];

    /**
     * @var array
     */
    private static $dsnParamMap = [
        'host'   => 'host',
        'port'   => 'port',
        'dbName' => 'dbname',
    ];

    /**
     * @var array
     */
    private static $symfonyParamMap = [
        'host'     => 'database_host',
        'port'     => 'database_port',
        'dbName'   => 'database_name',
        'user'     => 'database_user',
        'password' => 'database_password',
    ];

    /**
     * @var array
     */
    private static $requiredSymfonyParams = [
        'database_name',
    ];

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $content Symfony parameters file content
     *
     * @return MySqlCredentials
     * @throws \InvalidArgumentException
     */
    public static function fromSymfonyParamsFile($content)
    {
        $params = Yaml::parse($content);

        if (!isset($params['parameters'])) {
            throw new \InvalidArgumentException('Symfony parameters file is invalid: unable to find root element "parameters".');
        }

        $params = $params['parameters'];

        foreach (self::$requiredSymfonyParams as $param) {
            if (!isset($params[$param])) {
                throw new \InvalidArgumentException(
                    sprintf('Unable to create MySQL credentials object from Symfony parameters: required parameter "%s" is missing.', $param)
                );
            }
        }

        $credentials = new self();

        foreach (self::$symfonyParamMap as $property => $param) {
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
    public function toClientArgString($includeDbName = true)
    {
        $args = [];

        foreach (self::$clientArgMap as $property => $arg) {
            if ('dbName' === $property && !$includeDbName) {
                continue;
            }
            if (null !== $this->$property) {
                $args[] = '-'.$arg.$this->$property;
            }
        }

        return implode(' ', $args);
    }

    /**
     * @return string
     */
    public function toDsn()
    {
        $params = [];

        foreach (self::$dsnParamMap as $property => $param) {
            if (null !== $this->$property) {
                $params[] = implode('=', [$param, $this->$property]);
            }
        }

        return 'mysql:'.implode(';', $params);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword()
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
