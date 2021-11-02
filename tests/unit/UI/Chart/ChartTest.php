<?php 
use PHPUnit\Framework\TestCase;
use Gibbon\UI\Chart\Chart;

class ChartTest extends TestCase {
    public function testSetLabels() {
        $labels = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

        $chart = Chart::create('line_set_labels', 'line');
        $chart->setLabels($labels);
        $config = $chart->getConfig();

        // Test using sequence array
        $this->assertEquals(count($labels), count($config['data']['labels']));
    }

    public function testSetOptions() {
        $options = array(
            'responsive'=>true,
            'maintainAspectRatio'=>false,
            'title' => array(
                'display' => true,
                'text' => 'ChartFactory'
            ),
            'tooltip'=> array(
                    'mode'=> 'index',
                    'intersect'=> false,
            ),
            'hover'=> array(
                'mode'=> 'nearest',
                'intersect'=> true
            ),
            'scales'=> array(
                'x'=> array(
                    'display'=> true,
                    'title'=> array(
                        'display'=> true,
                        'labelString'=> 'Month'
                    )
                ),
                'y'=> array(
                    'display'=> true,
                    'title'=> array(
                        'display'=> true,
                        'labelString'=> 'Value'
                    )
                )
            )
        );

        $chart = Chart::create('line_test_set_options', 'line');
        $chart->setOptions($options);
        $config = $chart->getConfig();
        $this->assertEquals($options, $config['options']);
    }

    public function testUseFillZero() {
        $chart = Chart::create('line_test_use_fill_zero', 'line');
        $labels = array('January', 'February');
        $chart->setLabels($labels);
        $chart->useFillZero(true);
        $dataset = $chart->addDataset('2017', '2017');
        $this->assertEquals(count($labels), count($dataset->getData()));

        foreach ($dataset->getData() as $value) {
            $this->assertEquals($value, 0);
        }
    }

    public function testuseDefaultColors() {
        $chart = Chart::create('line_test_use_rainbow_color', 'line');
        $chart->setLabels(array('January', 'February', 'March', 'April'));
        $chart->useFillZero(true);
        $dataset = $chart->addDataset('2017','2017');
        $dataset->setProperty('backgroundColor', 'rgba(0,0,0,1)');
        
        $chart->useDefaultColors(false);
        $config = $chart->getConfig();
        $this->assertEquals($config['data']['datasets'][0]['backgroundColor'], 'rgba(0,0,0,1)');
        
        $chart->useDefaultColors(true);
        $config = $chart->getConfig();
        $this->assertNotEquals($config['data']['datasets'][0]['backgroundColor'], 'rgba(0,0,0,1)');
    }

    public function testaddDataset() {
        $chart = Chart::create('line_test_create_dataset', 'line');
        $dataset = $chart->addDataset('2017', '2017');
        $this->assertEquals($chart->hasDataset('2017'), true);
        $this->assertEquals($chart->dataset('2017'), $dataset);

        try {
            $chart->addDataset('2017', '2017');
        } catch(InvalidArgumentException $e) {
            return;
        }

        $this->fail();
    }

    public function testInvalidDataset() {
        $chart = Chart::create('line_test_invalid_dataset', 'line');
        
        $this->expectException('InvalidArgumentException');
        $chart->dataset('2018');
    }

    public function testGetConfig() {
        $labels = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $options = array(
            'responsive'=>true,
            'maintainAspectRatio'=>false,
            'title' => array(
                'display' => true,
                'text' => 'ChartFactory'
            ),
            'tooltip'=> array(
                    'mode'=> 'index',
                    'intersect'=> false,
            ),
            'hover'=> array(
                'mode'=> 'nearest',
                'intersect'=> true
            ),
            'scales'=> array(
                'x'=> array(
                    'display'=> true,
                    'title'=> array(
                        'display'=> true,
                        'labelString'=> 'Month'
                    )
                ),
                'y'=> array(
                    'display'=> true,
                    'title'=> array(
                        'display'=> true,
                        'labelString'=> 'Value'
                    )
                )
            )
        );
        $indonesianChartData = array(rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand());
        $englishChartData = array(rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand(), rand());
        $chart = Chart::create('line_test_get_config', 'line');
        $chart->setLabels($labels);
        $chart->setOptions($options);
        $chart->useDefaultColors(true);
        $indonesianDataset = $chart->addDataset('indonesian', 'Indonesian');
        $englishDataset = $chart->addDataset('english', 'English');

        $indonesianDataset->setData($indonesianChartData);
        $englishDataset->setData($englishChartData);

        $config = $chart->getConfig();
        $this->assertEquals($config['data']['labels'], $labels);
        $this->assertEquals(count($config['data']['datasets']), 2);
        $this->assertEquals(count($config['data']['datasets'][0]['data']), 12);
        $this->assertEquals($config['options'], $options);
    }

    public function testGetConfigPieChart() {
        $sampleData = array(
            'Like'=> rand(20, 100),
            'Hate'=> rand(20, 100),
            'Simple'=> rand(20, 100)
        );
        $colorMap = array(
            'Like'=> 'rgba(237,35,73,.6)',
            'Hate'=> 'rgba(56,140,203,.6)',
            'Simple'=> 'rgba(82,203,56,.6)'
        );
        
        $chart = Chart::create('pie_test_get_config_pie1', 'pie');
        $chart2 = Chart::create('pie_test_get_config_pie2', 'pie');

        $chart->setLabels(array_keys($sampleData));
        $chart2->setLabels(array_keys($sampleData));
        
        $chart->useFillZero(true);
        $chart2->useFillZero(true);
        
        $chart2->useDefaultColors(true);

        $dataset = $chart->addDataset('vote', 'vote');
        $dataset2 = $chart2->addDataset('vote', 'vote');
        $dataset->setData(array_values($sampleData));
        $dataset2->setData(array_values($sampleData));
        $dataset->setProperties(array('backgroundColor'=> array_values($colorMap)));

        $config = $chart->getConfig();
        $config2 = $chart2->getConfig();

        $this->assertEquals(count($config['data']['datasets'][0]['data']), count($sampleData));
        $this->assertEquals(count($config2['data']['datasets'][0]['data']), count($sampleData));

        $this->assertEquals(count($config['data']['datasets'][0]['backgroundColor']), count($sampleData));
        $this->assertEquals(count($config2['data']['datasets'][0]['backgroundColor']), count($sampleData));
    }

    public function testSetElementId() {
        $chart = Chart::create('test_set_element_id', 'line');
        $this->assertEquals('test_set_element_id', $chart->getElementId());
        $chart->setElementId('line_chart');
        $this->assertEquals('line_chart', $chart->getElementId());
    }
}
