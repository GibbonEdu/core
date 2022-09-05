<?php

namespace Gibbon\Module\Reports;

use Gibbon\Contracts\Database\Connection;
use Gibbon\Module\Reports\ReportData;
use Faker\Generator;
use ReflectionClass;
use ReflectionException;

/**
 * The DataFactory's only concern is building sets of ReportData for a given set of identifiers. 
 */
class DataFactory
{
    protected $pdo;
    protected $namespace = 'Gibbon\Module\Reports\Sources';
    protected $assetPath = '';

    protected $sources = [];

    /**
     * Constructor.
     * 
     * @param ReportTemplate $template
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Sets the custom asset path, to allow manually loading data sources.enabledButton
     *
     * @param string $assetPath
     */
    public function setAssetPath($assetPath)
    {
        $this->assetPath = $assetPath;
    }

    /**
     * Returns an array of ReportData objects for a given array of identifiers and sources.
     * 
     * @param array $identifiers
     * @param array $sources
     * @return array
     */
    public function buildReportData($identifiers, $sources = [])
    {
        $this->constructSources($sources);
        $data = array();

        foreach ($identifiers as $ids) {
            $report = new ReportData($ids);

            foreach ($sources as $alias => $className) {
                if ($source = $this->get($className)) {
                    $report->addData($alias, $source->getData($ids));
                }
            }

            $data[] = $report;
        }

        return $data;
    }
    
    /**
     * Returns an array with a single ReportData object, containing fake values generated for each DataSource.
     * 
     * @param array $sources
     * @return array
     */
    public function buildMockData(Generator $faker, $sources = [])
    {
        $this->constructSources($sources);

        $report = new ReportData([0]);

        foreach ($sources as $alias => $className) {
            if ($source = $this->get($className)) {
                
                // Build formatted faker data recursively
                $formatData = function ($schema, $data = []) use (&$faker, &$formatData) {
                    foreach ($schema as $key => $value) {
                        if (is_array($value) && is_string($value[0] ?? null)) {
                            // Associative array: build fake data
                            $formatter = $value[0];
                            if ($formatter == 'sameAs') {
                                $data[$key] = str_replace(array_keys($schema), array_values($data), $value[1] ?? '');
                            } else {
                                $data[$key] = $faker->format($formatter, array_slice($value, 1)) ?? $key;
                            }
                        } elseif (is_array($value)) {
                            // Numeric array: recurse
                            $data[$key] = $formatData($value);
                        } else {
                            // Non-array: use raw value
                            $data[$key] = $value;
                        }
                    }

                    return $data;
                };

                $mockSchema = $source->getSchema();
                $mockData = $formatData($mockSchema);

                $report->addData($alias, $mockData);
            }
        }

        return [$report];
    }

    /**
     * Adds a concrete DataSource instance to the factory.
     * 
     * @param string $alias
     * @param DataSource $source
     */
    public function add($className, DataSource $source)
    {
        $this->sources[$className] = $source;
        return $source;
    }

    /**
     * Gets or creates and returns a concrete DataSource for the given alias. Returns false otherwise.
     * 
     * @param string $alias
     * @return DataSource|bool
     */
    public function get($className)
    {
        return $this->hasSource($className)
            ? $this->sources[$className]
            : $this->createSource($className);
    }
    
    /**
     * Returns true if the factory can return a source for the given alias.
     * 
     * @param string $alias
     * @return bool
     */
    public function hasSource($className)
    {
        return array_key_exists($className, $this->sources);
    }

    /**
     * Creates instances of each DataSource for the given array of aliases.
     * 
     * @param array $aliases
     */
    protected function constructSources($sources)
    {
        if (empty($sources) || !is_array($sources)) return;

        foreach ($sources as $alias => $className) {
            if ($source = $this->createSource($className)) {
                $this->sources[$className] = $source;
            }
        }
    }

    /**
     * Creates an instance of a DataSource for the given alias if a definition exists. Returns false otherwise.
     * 
     * @param string $alias
     * @return object|bool
     */
    protected function createSource($className)
    {
        // Automatically include core sources
        $class = $this->namespace.'\\'.$className;
        try {
            $reflection = new ReflectionClass($class);
            $instance = $reflection->newInstanceArgs([$this, $this->pdo]);
            return $this->add($className, $instance);
        } catch (ReflectionException $e) {
        }

        // Manually create the custom source
        if (class_exists($className)) {
            return new $className($this, $this->pdo);
        }

        // Manually include custom source includes (lazy-load)
        $filePath = $this->assetPath.'/sources/'.$className.'.php';
        if (!class_exists($className) && is_file($filePath)) {
            try {
                include $filePath;
            } catch (\Exception $e) {
                return false;
            }

            return class_exists($className)
                ? new $className($this, $this->pdo)
                : false;
        }

        return false;
    }
}
