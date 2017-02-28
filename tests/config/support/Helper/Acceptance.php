<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Grab all values from a form (generally for the purposes of restoring later)
     *
     * @param    string  $selector
     * @return   [type]
     */
    public function grabAllFormValues($selector = '#content form') {
        $elements = $this->getModule('PhpBrowser')->_findElements("$selector input, $selector textarea, $selector select");

        $formValues = array();
        foreach ($elements as $element) {
            $type = $element->getAttribute('type');
            if ($type == 'submit' || $type == 'button') continue;

            $name = $element->getAttribute('name');
            $value = $element->getAttribute('value');

            switch($type) {
                case 'checkbox':    if ($element->hasAttribute('checked')) {
                                        $value = ($element->hasAttribute('value'))? $element->getAttribute('value') : 'on';
                                    }
                                    break;
            }
            
            $formValues[$name] = $value;
        }
        
        return $formValues;
    }
}
