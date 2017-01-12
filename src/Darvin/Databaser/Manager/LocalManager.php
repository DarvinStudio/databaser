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

/**
 * Local manager
 */
class LocalManager
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
     * @param string $projectPath Project path
     */
    public function __construct($projectPath)
    {
        $this->projectPath = $projectPath;

        $this->mySqlCredentials = null;
    }

    /**
     * @return \Darvin\Databaser\MySql\MySqlCredentials
     * @throws \RuntimeException
     */
    public function getMySqlCredentials()
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
