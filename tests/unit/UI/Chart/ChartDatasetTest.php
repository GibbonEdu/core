<?php 
use PHPUnit\Framework\TestCase;
use Gibbon\UI\Chart\Chart;

class ChartDatasetTest extends TestCase {

    public function testGetLabel() {
        $chart = Chart::create('line_test_get_label', 'line');
        $dataset = $chart->addDataset('data_january', 'January');
        $this->assertEquals($dataset->getLabel(), 'January');
    }

    public function testSetProperty() {
        $chart = Chart::create('line_test_set_property', 'line');
        $dataset = $chart->addDataset('data_january', 'January');
        $dataset->setProperty('backgroundColor', 'rgba(0,0,0,.4)');
        $this->assertEquals($dataset->getProperties(), array('backgroundColor'=> 'rgba(0,0,0,.4)'));
        $dataset->setProperty('borderColor', 'rgba(0,0,0,.8)');
        $this->assertEquals($dataset->getProperties(), 
                array(
                        'backgroundColor' => 'rgba(0,0,0,.4)', 
                        'borderColor' => 'rgba(0,0,0,.8)'
                    )
        );
        $dataset->setProperty('backgroundColor', 'rgba(0,0,0,.5)');
        $this->assertEquals($dataset->getProperties(), 
                array(
                        'backgroundColor' => 'rgba(0,0,0,.5)', 
                        'borderColor' => 'rgba(0,0,0,.8)'
                    )
        );

        $this->expectException('InvalidArgumentException');
        $dataset->setProperty('data', array(1,2));
    }

    public function testSetProperties() {
        $chart = Chart::create('line_test_set_properties', 'line');
        $dataset = $chart->addDataset('data_january', 'January');
        $dataset->setProperties(array(
                        'backgroundColor' => 'rgba(0,0,0,.4)', 
                        'borderColor' => 'rgba(0,0,0,.8)'
                    ));
        $this->assertEquals($dataset->getProperties(), 
                array(
                        'backgroundColor' => 'rgba(0,0,0,.4)', 
                        'borderColor' => 'rgba(0,0,0,.8)'
                    )
        );
        $dataset->setProperties(array(
                        'backgroundColor' => 'rgba(0,0,0,.4)'
                    ));
        $this->assertEquals($dataset->getProperties(), 
                array(
                        'backgroundColor' => 'rgba(0,0,0,.4)'
                    )
        );
    }

    public function testSetPropertiesUsingSequenceArray() {
        $chart = Chart::create('line_test_set_properties_sequence_arr', 'line');
        $dataset = $chart->addDataset('data_january', 'January');
        $this->expectException('InvalidArgumentException');
        $dataset->setProperties(array(
            array(
                    'backgroundColor' => 'rgba(0,0,0,.4)', 
                    'borderColor' => 'rgba(0,0,0,.8)'
                )
        ));   
    }

    public function testSetPropertiesWithDataAndLabel() {
        $chart = Chart::create('line_test_set_properties_with_data_label', 'line');
        $dataset = $chart->addDataset('data_january', 'January');
        $this->expectException('InvalidArgumentException');
        $dataset->setProperties(array(
            'backgroundColor' => 'rgba(0,0,0,.4)', 
            'borderColor' => 'rgba(0,0,0,.8)',
            'label'=>'January',
            'data'=> array(0,1)
        ));   
    }

    public function testSetData() {
        $chart = Chart::create('line_test_set_data', 'line');
        $chart->setLabels(array('January', 'February', 'Maret', 'April'));
        $dataset = $chart->addDataset('2017', '2017');
        $dataset->setData(array(4, 10, 2, 13));
        $this->assertEquals($chart->dataset('2017')->getData(), array(4, 10, 2, 13));
        $dataset->setData(1, 31);
        $this->assertEquals($chart->dataset('2017')->getData(), array(4, 31, 2, 13));
        
        try {
            $chart->dataset('2017')->setData(10, 20);
        } catch(InvalidArgumentException $e) {
            return;
        }

        $this->fail();
    }

    public function testAppendData() {
        $chart = Chart::create('line_test_append_data', 'line');
        $chart->setLabels(array('January', 'February', 'Maret', 'April'));
        $dataset = $chart->addDataset('2017', '2017');
        $dataset->appendData(20);
        $this->assertEquals($chart->dataset('2017')->getData(), array(20));
        $dataset->appendData(21);
        $this->assertEquals($chart->dataset('2017')->getData(), array(20, 21));
    }
}
