<?php

namespace Gibbon\Install;

use Gibbon\Install\Config;
use Twig\Environment;

class Installer
{

    /**
     * Twig template engine to use for config file rendering.
     *
     * @var \Twig\Environment
     */
    protected $templateEngine;

    /**
     * Constructor
     *
     * @param string      $installPath     The system path for installing Gibbon.
     * @param Environment $templateEngine  The template engine to generate config file from.
     */
    public function __construct(
        Environment $templateEngine
    ) {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Generate configuration file from twig template.
     *
     * @param Config $config    The config object to generate file from.
     * @param string $path      The full path (includes filename) to generate.
     * @param string $template  The template file to use.
     *
     * @return self
     */
    public function createConfigFile(
        Context $context,
        Config $config,
        string $template = 'installer/config.twig.html'
    )
    {
        $contents = $this->templateEngine->render(
            $template,
            static::processConfigVars($config->getVars())
        );

        // Write config contents
        $fp = fopen($context->getConfigPath(), 'wb');
        fwrite($fp, $contents);
        fclose($fp);

        if (!file_exists($context->getConfigPath())) { //Something went wrong, config.php could not be created.
            throw new \Exception(__('../config.php could not be created.'));
        }
        return $this;
    }

    /**
     * Process config variables into string literals stored in string.
     *
     * @param array variables
     *      An array of config variables to be passed into config
     *      file template.
     *
     * @return array
     *      The variables forced to be string type and properly quoted.
     */
    public static function processConfigVars(array $variables)
    {
        $variables += [
            'databaseServer' => '',
            'databaseUsername' => '',
            'databasePassword' => '',
            'databaseName' => '',
            'guid' => '',
        ];
        return array_map(function ($value) {
            return var_export((string) $value, true); // force render into string literals
        }, $variables);
    }
}
