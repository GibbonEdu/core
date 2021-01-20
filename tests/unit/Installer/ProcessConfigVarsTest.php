<?php

namespace Gibbon\Tests\UnitTest\Installer;

use PHPUnit\Framework\TestCase;
use Gibbon\Services\CoreServiceProvider;

require_once __DIR__ . '/../../../installer/installerFunctions.php';

/**
 * @covers process_config_vars function and config file template
 */
class ProcessConfigVarsTest extends TestCase {

    private $PatternNoSyntaxError = '/^No syntax errors detected in /';

    /**
     * @inherit
     */
    public function setUp() {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../resources/templates');
        $this->templateEngine = new \Twig\Environment($loader);
    }

    /**
     * Mocking render a very raw PHP with all the variables in
     * a given array with key as variable name.
     *
     * @return string
     *     Full path to the temporary mock php file rendered.
     */
    private function mockPhpRender(array $variables): string {
        $filename = @tempnam(sys_get_temp_dir(), 'ProcessConfigVarsTestMockPhp') . '.config.php';
        $fh = fopen($filename, 'w');
        fwrite($fh, "<?php\n");
        foreach ($variables as $key => $value) {
            fwrite($fh, "\${$key} = {$value};\n");
        }
        fclose($fh);
        return $filename;
    }

    /**
     * Mocking render given variables with the production config template.
     *
     * @return string
     *     Full path to the temporary mock config file rendered.
     */
    private function mockTemplateRender(array $variables): string {
        $filename = @tempnam(sys_get_temp_dir(), 'ProcessConfigVarsTestMockTemplate') . '.config.php';
        $fh = fopen($filename, 'w');
        fwrite($fh, $this->templateEngine->render('installer/config.twig.html', $variables));
        fclose($fh);
        return $filename;
    }

    /**
     * Include a given PHP file in a function scope and return the variables
     * value of the supplied keys.
     *
     * @return array
     *     An array of variables of the supplied keys as variable names.
     */
    private function extractVariablesFrom(string $filename, array $keys) {
        return (function ($filename, $keys) {
            include $filename;
            return compact(...$keys);
        })($filename, $keys);
    }

    /**
     * @covers config_file_template($configData)
     */
    public function testBasicCall()
    {
        $inputConfig = [
            'databaseServer' => 'myDatabaseServer',
            'databaseUsername' => 'myDatabaseUsername',
            'databasePassword' => 'myDatabasePassword',
            'databaseName' => 'myDatabaseName',
            'guid' => 'myGuid',
        ];

        // check the syntax with mock config rendering function
        // to see if all variables are valid to print raw
        $filename = $this->mockPhpRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        unlink($filename);

        // check the syntax of the render result of the would-be config file
        // with the template file and given config data ($inputConfig)
        $filename = $this->mockTemplateRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        $resultConfig = $this->extractVariablesFrom($filename,
            ['databaseServer', 'databaseUsername', 'databasePassword', 'databaseName', 'guid']);
        $this->assertEquals($inputConfig, $resultConfig, 'Unexpected set of result config variables');
        unlink($filename);
    }

    /**
     * @covers config_file_template($configData)
     */
    public function testEmptyArrayCall()
    {
        $inputConfig = [];

        // check the syntax with mock config rendering function
        // to see if all variables are valid to print raw
        $filename = $this->mockPhpRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        unlink($filename);

        // check the syntax of the render result of the would-be config file
        // with the template file and given config data ($inputConfig)
        $filename = $this->mockTemplateRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        $resultConfig = $this->extractVariablesFrom($filename,
            ['databaseServer', 'databaseUsername', 'databasePassword', 'databaseName', 'guid']);
        $this->assertEquals(
            [
                'databaseServer' => '',
                'databaseUsername' => '',
                'databasePassword' => '',
                'databaseName' => '',
                'guid' => '',
            ],
            $resultConfig,
            'Unexpected set of result config variables'
        );
        unlink($filename);
    }

    /**
     * @covers config_file_template($configData)
     */
    public function testDangerousDataCall()
    {
        $dangerousStr = 'everything dangerous: \\/"\'';
        $inputConfig = [
            'databasePassword' => $dangerousStr,
            'databaseServer' => $dangerousStr,
            'databaseUsername' => $dangerousStr,
            'databasePassword' => $dangerousStr,
            'databaseName' => $dangerousStr,
            'guid' => $dangerousStr,
        ];

        // check the syntax with mock config rendering function
        // to see if all variables are valid to print raw
        $filename = $this->mockPhpRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        unlink($filename);

        // check the syntax of the render result of the would-be config file
        // with the template file and given config data ($inputConfig)
        $filename = $this->mockTemplateRender(process_config_vars($inputConfig));
        $this->assertFileExists($filename, 'The temporary generated test target not found.');
        $result = exec('php -l ' . $filename);
        $this->assertTrue(
            preg_match($this->PatternNoSyntaxError, $result) !== false,
            'The generated config contains syntax error(s)'
        );
        $resultConfig = $this->extractVariablesFrom($filename,
            ['databaseServer', 'databaseUsername', 'databasePassword', 'databaseName', 'guid']);
        $this->assertEquals($inputConfig, $resultConfig, 'Unexpected set of result config variables');
        unlink($filename);
    }
}

