<?php
namespace Gibbon\Forms\Layout;

use Gibbon\Forms\OutputableInterface;

class WebLink extends Element
{
    protected $embeddedElements = array();
    protected $params = array();

    public function __construct($content = '')
    {
        $this->setURL('');
        parent::__construct($content);
    }

    public function onClick($value)
    {
        $this->setAttribute('onClick',$value);
        return $this;
    }

    /**
     * Sets the link href attribute.
     * @param string $url
     * @return self
     */
    public function setURL($url)
    {
        return $this->setAttribute('href', $url);
    }

    /**
     * Gets the link href attribute.
     * @return string
     */
    public function getURL()
    {
        return $this->getAttribute('href');
    }

    /**
     * Sets the link target attribute.
     * @param string $target
     * @return self
     */
    public function setTarget($target)
    {
        return $this->setAttribute('target', $target);
    }

    /**
     * Gets the link target attribute.
     * @return string
     */
    public function getTarget()
    {
        return $this->getAttribute('target');
    }

    /**
     * Sets the link rel attribute.
     * @param string $rel
     * @return self
     */
    public function setRel($rel)
    {
        return $this->setAttribute('rel', $rel);
    }

    /**
     * Gets the link rel attribute.
     * @return string
     */
    public function getRel()
    {
        return $this->getAttribute('rel');
    }

    /**
     * Add a confirmation message to display on click.
     * @param string $message
     * @return self
     */
    public function addConfirmation($message)
    {
        $this->setAttribute('onclick', "return confirm(\"".__($message)."\")");
        
        return $this;
    }

    /**
     * Adds a URL parameter to be appended to the link URL.
     * @param string $name
     * @param string $value
     * @return self
     */
    public function addParam($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Adds an array of URL parameters to be appended to the link URL.
     * @param array $values
     * @return self
     */
    public function addParams($values)
    {
        if (is_array($values)) {
            $this->params = array_replace($this->params, $values);
        }

        return $this;
    }

    /**
     * Adds an embedded element to output inside the link tag.
     * @param OutputtableInterface $element
     * @return self
     */
    public function addEmbeddedElement($element)
    {
        if ($element instanceof OutputableInterface) {
            $this->embeddedElements[] = $element;
        }

        return $this;
    }
    
    /**
     * Sets an array of embedded elements to output inside the link tag.
     * @param OutputtableInterface $element
     * @return self
     */
    public function setEmbeddedElements($elements)
    {
        $elements = is_array($elements)? $elements : array($elements);

        foreach($elements as $element) {
            $this->addEmbeddedElement($element);
        }

        return $this;
    }

    /**
     * Gets the output of all embedded elements as a string.
     * @return string
     */
    public function getEmbeddedElements()
    {
        $output = '';

        foreach($this->embeddedElements as $element) {
            $output .= $element->getOutput();
        }

        return $output;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    public function getElement()
    {
        if (!empty($this->params)) {
            $separator = (stripos($this->getURL(), '?') === false)? '?' : '&';
            $this->setURL($this->getURL().$separator.http_build_query($this->params));
        }

        return '<a ' . $this->getAttributeString() . '>' . $this->content . $this->getEmbeddedElements() . '</a>';
    }
}
