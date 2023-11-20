<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\UI\Chart;

class Chart
{
    protected $elementID = 'chart';
    protected $chartType = '';

    protected $labels = [];
    protected $options = ['maintainAspectRatio' => false];
    protected $datasets = [];
    protected $functions = [];
    protected $metadata = [];

    protected $useFillZero = false;
    protected $useDefaultColors = true;

    protected $defaultColors = [
        'rgba(153, 102, 255, 1.0)',
        'rgba(255, 99, 132, 1.0)',
        'rgba(255, 206, 86, 1.0)',
        'rgba(54, 162, 235, 1.0)',
        'rgba(133, 233, 194, 1.0)',
        'rgba(255, 159, 64, 1.0)',
        'rgba(237, 85, 88, 1.0)',
        'rgba(75, 192, 192, 1.0)',
        'rgba(161, 89, 173, 1.0)',
        'rgba(29, 109, 163, 1.0)',
        'rgba(152, 221, 95, 1.0)',
    ];

    private $allowedChartTypes = [
        'bar',
        'horizontalBar',
        'doughnut',
        'line',
        'pie',
        'polarArea',
        'radar',
        'bubble',
        'scatter',
    ];

    /**
     * Chart Constructor
     *
     * @param string $elementID
     * @param string $chartType
     */
    public function __construct($elementID, $chartType)
    {
        // Prevent decimal numbers from using commas, which causes invalid data when encoded to JSON.
        setlocale(LC_NUMERIC, 'C');

        if (!preg_match('/^[a-zA-Z_]+[0-9a-zA-Z_]*$/', $elementID)) {
            throw new \InvalidArgumentException('The chartID value must be a valid HTML element id.');
        }

        if (!in_array($chartType, $this->allowedChartTypes)) {
            throw new \InvalidArgumentException(sprintf('The chart type %s is not one of the available types in the chart.js library.', $chartType));
        }

        $this->setElementId($elementID);
        $this->chartType = $chartType;
    }

    public static function create($elementID, $chartType)
    {
        return new self($elementID, $chartType);
    }

    /**
     * Get the HTML element id of the chart.
     * @return string
     */
    public function getElementID()
    {
        return $this->elementID;
    }

    /**
     * Set the HTML element ID for the chart.
     * @param string $id
     */
    public function setElementID($id)
    {
        $this->elementID = $id;

        return $this;
    }

    /**
     * Get a pre-defined color by numeric index, using defaultColors as a circular array.
     *
     * @param  number $index
     * @return string
     */
    public function getColor($index)
    {
        $n = $index % count($this->defaultColors);

        return $this->defaultColors[$n];
    }

    /**
     * Set the default color array to apply to data sets.
     *
     * @param array $defaultColors
     * @return self
     */
    public function setColors($defaultColors)
    {
        $this->defaultColors = $defaultColors;

        return $this;
    }

    public function setColorOpacity($opacity)
    {
        $this->defaultColors = array_map(function ($color) use ($opacity) {
            return str_replace('1.0', floatval($opacity), $color);
        }, $this->defaultColors);

        return $this;
    }

    /**
     * Set an array of labels.
     * @param array $labels
     * @return self
     */
    public function setLabels($labels)
    {
        // Use only the array keys if an associative array is passed in.
        if (array_values($labels) !== $labels) {
            $labels = array_keys($labels);
        }

        $this->labels = $labels;

        return $this;
    }

    /**
     * Set options of chart
     * @param array $options
     * @return self
     */
    public function setOptions($options)
    {
        $this->options = array_replace($this->options, $options);

        return $this;
    }

    /**
     * Add miscellaneous data to the chart config. Useful for onClick functions.
     *
     * @param array $metadata
     * @return self
     */
    public function setMetaData($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function setLegend($value)
    {
        $this->options['plugins']['legend'] = is_array($value)
            ? $value
            : ['display' => $value == true];

        return $this;
    }

    public function setTitle($value)
    {
        $this->options['title'] = is_array($value)
            ? $value
            : ['display' => !empty($value), 'text' => $value];

        return $this;
    }

    /**
     * Datasets will have their values initialized to 0.
     *
     * @return self
     */
    public function useFillZero($value)
    {
        $this->useFillZero = $value;

        return $this;
    }

    /**
     * Use the default colors for backgroundColor & borderColor properties on datasets.
     *
     * @return self
     */
    public function useDefaultColors($value)
    {
        $this->useDefaultColors = $value;

        return $this;
    }

    /**
     * Add an onClick event to the chart. Can be the name of a function or the function itself.
     *
     * @param string $function
     * @return self
     */
    public function onClick($function, $pointerOnHover = true)
    {
        $this->options['events'] = ['click', 'mousemove'];
        $this->options['onClick'] = $this->addFunction($function);

        if ($pointerOnHover) {
            $this->onHover('function(event, elements) { document.body.style.cursor = (elements.length) ? "pointer" : "default";}');
        }
        return $this;
    }

    /**
     * Add an onHover event to the chart. Can be the name of a function or the function itself.
     *
     * @param string $function
     * @return self
     */
    public function onHover($function)
    {
        $this->options['events'] = ['click', 'mousemove'];
        $this->options['onHover'] = $this->addFunction($function);

        return $this;
    }

    /**
     * Add a custom tooltip function to the chart. Can be the name of a function or the function itself.
     *
     * @param string $function
     * @return self
     */
    public function onTooltip($labelFunction = null, $titleFunction = null)
    {
        if ($labelFunction) {
            $this->options['plugins']['tooltip']['callbacks']['label'] = $this->addFunction($labelFunction);
        }

        if ($titleFunction) {
            $this->options['plugins']['tooltip']['callbacks']['title'] = $this->addFunction($titleFunction);
        }

        return $this;
    }

    /**
     * Get a function by index.
     *
     * @param  string $name
     * @return string
     */
    public function getFunction($index)
    {
        return isset($this->functions[$index])? $this->functions[$index] : '';
    }

    /**
     * Add a function to it can be inserted into the config after json_encoding.
     * Returns a string identifier used to inject the function into the config.
     *
     * @param string $function
     * @return string
     */
    public function addFunction($function)
    {
        $index = count($this->functions);
        $this->functions[$index] = $function;

        return '__function:'.$index.'__';
    }

    /**
     * Add a new dataset with a given id and optional label.
     *
     * @param string $id
     * @param string $label
     */
    public function addDataset($id, $label = '')
    {
        if ($this->hasDataset($id)) {
            throw new \InvalidArgumentException(sprintf('A dataset with the id %s is already defined.', $id));
        }

        $dataset = new ChartDataset($label);
        $this->datasets[$id] = $dataset;

        if ($this->useFillZero) {
            $dataset->setData(array_fill(0, count($this->labels), 0));
        }

        return $dataset;
    }

    /**
     * Get a dataset for a given id.
     *
     * @param  string $id
     * @return ChartDataset
     */
    public function dataset($id)
    {
        if (!$this->hasDataset($id)) {
            throw new \InvalidArgumentException(sprintf('Dataset %s is not defined', $id));
        }

        return $this->datasets[$id];
    }

    /**
     * Check if a dataset for the given id has been defined.
     *
     * @param  string  $id
     * @return boolean
     */
    public function hasDataset($id)
    {
        return isset($this->datasets[$id]);
    }

    /**
     * Generate chart configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = array(
            'type' => $this->chartType,
            'data'=> array(
                'datasets'=> []
            )
        );

        if (!empty($this->labels)) {
            $config['data']['labels'] = $this->labels;
        }

        // Index for the default color set
        $index = 0;

        foreach ($this->datasets as $dataset) {
            $chartDataset = $dataset->getProperties();
            $chartDataset['data'] = $dataset->getData();

            if (!empty($dataset->getLabel())) {
                $chartDataset['label'] = $dataset->getLabel();
            }

            if ($this->useDefaultColors) {
                if (in_array($this->chartType, array('doughnut', 'pie', 'polarArea'))) {
                    $chartDataset['backgroundColor'] = [];
                    $chartDataset['borderColor'] = 'rgba(0,0,0,0)';
                    foreach ($chartDataset['data'] as $key => $value) {
                        $chartDataset['backgroundColor'][] = $this->getColor($index);
                        $index++;
                    }
                    // Initialize the index again for next dataset (based on chart type)
                    $index = 0;
                } else {
                    $color = $this->getColor($index);
                    $chartDataset['pointBackgroundColor'] = $color;
                    $chartDataset['backgroundColor'] = $color;
                    $chartDataset['borderColor'] = $color;
                    $index++;
                }
            }

            $config['data']['datasets'][] = $chartDataset;
        }

        $config['options'] = $this->options;

        if (!empty($this->metadata)) {
            $config['metadata'] = $this->metadata;
        }

        return $config;
    }

    public function getScriptContents()
    {
        $output = '';
        $config = json_encode($this->getConfig(), JSON_NUMERIC_CHECK);
        $key = $this->getElementID();

        // Inject functions into the config. This is a workaround to enable javascript functions,
        // which need added after json_encode so it doesn't enclose them in ""s.
        foreach ($this->functions as $index => $function) {
            $config = str_replace('"__function:'.$index.'__"', $this->getFunction($index), $config);
        }

        $output .= sprintf('var chart_config_%s = %s;', $key, $config);
        $output .= sprintf('var chart_context_%s = document.getElementById("%s").getContext("2d");', $key, $this->getElementID());
        $loaderScript = sprintf('window.chart_%s = new Chart(chart_context_%s, chart_config_%s);', $key, $key, $key);
        $output .= sprintf("\n$(function() {\n%s\n});", $loaderScript) . "\n";

        return $output;
    }

    /**
     * Render a HTML canvas element and script tag for the current chart.
     *
     * @return string
     */
    public function render()
    {

        $canvas = '<div class="chart-container" style="position: relative; height:'.($this->options['height'] ?? '300px').'; width:'.($this->options['width'] ?? '100%').';">';
        $canvas .= '<canvas id="'.$this->getElementID().'" ></canvas>';
        $canvas .= '</div>';
        $script = '<script type="text/javascript">'.$this->getScriptContents().'</script>';

        return $canvas . $script;
    }
}
