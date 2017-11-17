<?php
namespace Gibbon\Forms\Input;

class WebLink extends Input
{
	protected $embeddedElements = array();
	protected $id;
	protected $url;

	public function __construct($id,$url)
	{
		$this->setName($id);
		$this->setURL($url);
	}

	public function getName()
	{
		return $this->id;
	}

	public function setName($name = '')
	{
		$this->id = $name;
		$this->setAttribute('id',$this->id);
		return $this;
	}

	public function setURL($url)
	{
		$this->setAttribute('href',$url);
		$this->url = $url;
		return $this;
	}

	public function getURL()
	{
		return $this->url;
	}

	//Expects an array
	public function setEmbeddedElements($elements)
	{
		foreach($elements as $element)
		{
			$this->addEmbeddedElement($element);
		}
		return $this;
	}

	public function addEmbeddedElement(Input $element)
	{
		array_push($this->embeddedElements,$element);
		return $this;
	}

	public function getEmbeddedElements()
	{
		$output = '';
		foreach($this->embeddedElements as $element)
		{
			$output .= $element->getElement();
		}
		return $output;
	}

	public function getElement()
	{
		return "<a " . $this->getAttributeString() . ">" . $this->getEmbeddedElements() . "</a>";
	}
}

?>
