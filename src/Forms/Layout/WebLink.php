<?php
namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;

class WebLink extends Element
{
    protected $embeddedElements = array();
    protected $params = array();

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

    public function addConfirmation($message)
    {
        $this->setAttribute('onclick', "return confirm(\"".__($message)."\")");
        
        return $this;
    }

    public function addParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    public function addParams($values)
    {
        if (is_array($values)) {
            $this->params = array_replace($this->params, $values);
        }

        return $this;
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
        if (!empty($this->params)) {
            $url = $this->getURL();
            $url .= (stripos($url, '?') !== false)? '&' : '?';
            $this->setURL($url.http_build_query($this->params));
        }

        return '<a ' . $this->getAttributeString() . '>' . $this->content . $this->getEmbeddedElements() . '</a>';
    }
}
