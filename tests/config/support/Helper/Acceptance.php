<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    /**
     * Grab all values from a form (generally for the purposes of restoring later)
     * Itterates over an array of DOMElement objects
     *
     * @param    string  $selector
     * @return   [type]
     */
    public function grabAllFormValues($selector = '#content form') {
        $elements = $this->getModule('PhpBrowser')->_findElements("$selector input, $selector textarea, $selector select");

        $formValues = array();
        foreach ($elements as $element) {
            $type = ($element->tagName == 'input')? $element->getAttribute('type') : $element->tagName;
            
            if ($type == 'submit' || $type == 'button') continue;

            $name = $element->getAttribute('name');
            $value = ($element->hasAttribute('value'))? $element->getAttribute('value') : '';

            switch($type) {
                case 'checkbox':    if ($element->hasAttribute('checked')) {
                                        $value = ($element->hasAttribute('value'))? $element->getAttribute('value') : 'on';
                                    }
                                    $formValues[$name] = $value;
                                    break;

                case 'radio':       if ($element->hasAttribute('checked')) {
                                        $value = ($element->hasAttribute('value'))? $element->getAttribute('value') : '';
                                        $formValues[$name] = $value;
                                    }
                                    break;

                case 'textarea':    $value = $element->nodeValue;
                                    $formValues[$name] = $value;
                                    break;

                case 'select':      $optionTags = $element->getElementsByTagName('option');
                                    for ($i = 0; $i < $optionTags->length; $i++ ) {
                                        if ($optionTags->item($i)->hasAttribute('selected') 
                                        && $optionTags->item($i)->getAttribute('selected') === "selected") {
                                            $value = $optionTags->item($i)->getAttribute('value');
                                        }
                                    }
                                    $formValues[$name] = $value;
                                    break;
            }
        }
        
        return $formValues;
    }
}
