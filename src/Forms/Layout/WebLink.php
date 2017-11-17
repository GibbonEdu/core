<?php
namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;

class WebLink extends Element
{
    protected $embeddedElements = array();

    public function __construct($content = '')
    {
        $this->setURL('#');
        parent::__construct($content);
    }

    public function setURL($url)
    {
        return $this->setAttribute('href', $url);
    }

    public function getURL()
    {
        return $this->getAttribute('href');
    }

    public function setTarget($target)
    {
        return $this->setAttribute('target', $target);
    }

    public function getTarget()
    {
        return $this->getAttribute('target');
    }

    public function setRel($rel)
    {
        return $this->setAttribute('rel', $rel);
    }

    public function getRel()
    {
        return $this->getAttribute('rel');
    }

    public function addEmbeddedElement($element)
    {
        if ($element instanceof OutputableInterface) {
            $this->embeddedElements[] = $element;
        }

        return $this;
    }
    
    public function setEmbeddedElements($elements)
    {
        $elements = is_array($elements)? $elements : array($elements);

        foreach($elements as $element) {
            $this->addEmbeddedElement($element);
        }

        return $this;
    }

    public function getEmbeddedElements()
    {
        $output = '';

        foreach($this->embeddedElements as $element) {
            $output .= $element->getOutput();
        }

        return $output;
    }

    public function getElement()
    {
        return '<a ' . $this->getAttributeString() . '>' . $this->content . $this->getEmbeddedElements() . '</a>';
    }
}
