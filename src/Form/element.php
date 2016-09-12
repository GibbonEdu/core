<?php
/**
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
/**
 */
namespace Gibbon\Form;

use Gibbon\core\trans ;
use Gibbon\core\session ;
use Symfony\Component\Yaml\Yaml ;

/**
 * Element Base
 *
 * @version	6th September 2016
 * @since	10th May 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Form
 */
class element
{
	/**
	 * Inject Record
	 *
	 * @version	10th May 2016
	 * @since	10th May 2016
	 * @param	object or array	$record	Data to insert into Class
	 * @return	void
	 */
	public function injectRecord($record)
	{
		foreach((array)$record as $name=>$value)
		{
			$this->$name = $value;
		}
		$this->id = '_'.$this->name ;
	}
	
	/**
	 * Set Required / Presence
	 *
	 * @version	17th June 2016
	 * @since	23rd May 2016
	 * @return	void
	 */
	public function setRequired($message = 'Entry Required!')
	{
		$this->required = true;
		$this->getValidate()->Presence = true ;
		$this->validate->presenceMessage = $message;
	}
	
	/**
	 * Get Required
	 *
	 * @version	23rd May 2016
	 * @since	23rd May 2016
	 * @return	stdClass
	 */
	public function getValidate()
	{
		if (empty($this->validate) && ! $this->validate instanceof \stdClass)
			$this->validate = new \stdClass();
		return $this->validate;
	}
	
	/**
	 * Set Required
	 *
	 * @version	15th June 2016
	 * @since	23rd May 2016
	 * @return	void
	 */
	public function validateOff()
	{
		$this->validate = false ;
		$this->required = false ;
	}
	
	/**
	 * Set Exclusion
	 *
	 * @version	23rd May 2016
	 * @since	23rd May 2016
	 * @param	string		$within		Comma separated list of excluded values.
	 * @param	string		$message	Message prompt to user.
	 * @return	void
	 */
	public function setExclusion($within, $message, $extra = null)
	{
		$this->getValidate()->Exclusion = true ;
		$this->validate->within = $within;
		$this->validate->exclusionMessage = $message ;
		$this->validate->exclusionExtras = $extra ;
	}
	
	/**
	 * Set Numericality
	 *
	 * @version	30th June 2016
	 * @since	24th May 2016
	 * @param	string		$message
	 * @param	integer		$min
	 * @param	integer		$max
	 * @param	boolean		$int	Only Integer
	 * @return	void
	 */
	public function setNumericality($message = 'Must be a number.', $min = null, $max = null, $int = false)
	{
		$this->getValidate()->Numericality = true ;
		$this->validate->numberMinimum = $min;
		$this->validate->numberMaximum = $max;
		$this->validate->numberMessageMinimum = $message ;
		$this->validate->numberMessageMaximum = $message ;
		$this->validate->numberMessage = $message ;
		$this->validate->onlyInteger = '';
		if ($int) $this->setInteger();
	}
	
	/**
	 * Set Integer
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @return	void
	 */
	public function setInteger()
	{
		$this->validate->integer = true ;
		$this->validate->onlyInteger = 'onlyInteger: true,';
		$this->validate->numberMessageInteger = 'Integer Only' ;
	}
	
	/**
	 * Set Number Maximum
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	integer		$max
	 * @param	string		$message
	 * @return	void
	 */
	public function setMax($max, $message = 'Number <= ')
	{
		$this->validate->numberMaximum = $max;
		$this->validate->numberMessageMaximum = $message ;
		if ($message === 'Number <= ')
			$this->validate->numberMessageMaximum .= $max;
	}
	
	/**
	 * Set Number Minimum
	 *
	 * @version	30th June 2016
	 * @since	30th June 2016
	 * @param	integer		$min
	 * @param	string		$message
	 * @return	void
	 */
	public function setMin($min, $message = 'Number >= ')
	{
		$this->validate->numberMinimum = $min;
		$this->validate->numberMessageMinimum = $message ;
		if ($message === 'Number >= ')
			$this->validate->numberMessageMinimum .= $min;
	}
	
	/**
	 * Set Format
	 *
	 * @version	27th May 2016
	 * @since	25th May 2016
	 * @param	string		$patterm	Regex Format String
	 * @param	string		$message	Message prompt to user.
	 * @return	void
	 */
	public function setFormat($pattern, $message)
	{
		$this->getValidate()->Format = true;
		$this->validate->pattern = $pattern;
		$this->validate->formatMessage = $message ;
	}

	/**
	 * Set Length
	 *
	 * @version	31st May 2016
	 * @since	27th May 2016
	 * @param	string		$message
	 * @param	integer		$min
	 * @param	integer		$max
	 * @return	void
	 */
	public function setLength($message = 'Length not valid!', $min = null, $max = null )
	{
		$this->getValidate()->Length = true ;
		$this->getValidate()->minLength = $min;
		$this->maxLength = $this->getValidate()->maxLength = $max;
		$this->getValidate()->lengthMessage = is_null($message) ? 'Length not valid!' : $message ;
	}

	/**
	 * Set Confirmation
	 *
	 * @version	17th June 2016
	 * @since	27th May 2016
	 * @param	string		$message
	 * @param	string		$match
	 * @return	void
	 */
	public function setConfirmation($message = 'Does not match!', $match)
	{
		$this->getValidate()->Confirmation = $match;
		$this->getValidate()->messageConfirmation = $message;
	}

	/**
	 * Set Email
	 *
	 * @version	1st July 2016
	 * @since	17th June 2016
	 * @param	string		$message
	 * @return	void
	 */
	public function setEmail( $message = 'Provide a valid email!' )
	{
		$this->getValidate()->Email = $message;
	}

	/**
	 * create Defaults
	 *
	 * @version	6th September 2016
	 * @since	1st june 2016
	 * @return	void
	 */
	public function createDefaults()
	{
		$this->row = new \stdClass() ;
		$this->col1 = new \stdClass() ;
		$this->col2 = new \stdClass() ;
		$this->element = new \stdClass() ;
		$this->span = new \stdClass() ;
		$this->name = null ;
		$this->value = null ;
		$this->id = null ;
		$this->validate = false ;
		$this->required = false ;
		$this->pleaseSelect = false ;
		$this->readOnly = false ;
		$this->elementOnly = false ;
		$this->autoComplete = false ;
		$this->additional = '' ;
	}

	/**
	 * create Defaults
	 *
	 * @version	2nd June 2016
	 * @since	2nd june 2016
	 * @return	void
	 */
	public function __construct()
	{
		$this->createDefaults();
	}

	/**
	 * set Theme Standards
	 *
	 * @version	9th July 2016
	 * @since	14th june 2016
	 * @params	array		$el			Theme Form Display Settings
	 * @return	void
	 */
	public function setThemeStandards($el)
	{
		if (isset($el[$this->element->name]))
		{
			$settings = $el[$this->element->name];
			$this->integrateThemeSettings($settings, $this);
		}
		$this->mergeClass();
	}

	/**
	 * integrate Theme Settings
	 *
	 * @version	14th June 2016
	 * @since	14th June 2016
	 * @return	void
	 */
	public function integrateThemeSettings($settings, $el)
	{
		foreach ($settings as $name=>$value)
		{
			if (is_array($value))
			{
				if (! isset($el->$name) || ! is_object($el->$name))
					$el->$name = new \stdClass();
				if (is_array($value))
					$this->integrateThemeSettings($value, $el->$name);
			}
			elseif ( empty($value) || $value == '~' || is_null($value))
			{
				if (! isset($el->$name) || ! is_object($el->$name))
					$el->$name = new \stdClass();
			}
			elseif (! empty($value) )
			{
				if (! isset($el->$name) || is_null($el->$name))
					$el->$name = $value;
			}
		}
	}
	
	/**
	 * insert Validation
	 *
	 * @version	8th July 2016
	 * @since	17th June 2016
	 * @param	Object		$el	Element
	 * @param	boolean		$script
	 * @return	void
	 */
	public function insertValidation($el, $script = false)
	{
		if (! isset($this->validation))
		{
			$session = new session();
			if ($session->isEmpty('gibbonThemeName'))
				$session->set('gibbonThemeName', 'Bootstrap');
			if (file_exists(GIBBON_ROOT . 'src/themes/' . $session->get('gibbonThemeName') . '/settings.yml'))
				$theme = Yaml::parse(file_get_contents(GIBBON_ROOT . 'src/themes/' . $session->get('gibbonThemeName') . '/settings.yml'));
			elseif (file_exists(GIBBON_ROOT . 'src/themes/Bootstrap/settings.yml'))
				$theme = Yaml::parse(file_get_contents(GIBBON_ROOT . 'src/themes/Bootstrap/settings.yml'));
			else
				throw new \Exception('The theme defaults file was not available to load', 31000 + __LINE__);
			$this->validation = $theme['validation'];
		}
		if (intval($script) !== intval($this->validation['asScript'])) return ;
		$val = '';
		if (! empty($el->validate->URL) && $el->validate->URL && isset($this->validation['URL']))
		{
			$val .= str_replace(array('{{message}}', '{{protocols}}', '{{id}}'), array(trans::__($el->validate->messageURL), $el->validate->protocolsURL, $el->id), $this->validation['URL']);
		}

		if (! empty($el->validate->Phone) && isset($this->validation['Phone']))
		{
//			$val .= str_replace(array('{{message}}', '{{countryCode}}', '{{id}}'), array(trans::__($el->validate->Phone), $el->validate->protocolsURL, $el->id), $this->validation['URL']);
		}

		if (! empty($el->validate->Date) && $el->validate->Date && isset($this->validation['Date']))
		{
			$el->validate->Length = $el->validate->Format = false ;
		}


		if (isset($el->validate->Format) && $el->validate->Format && isset($this->validation['Format']))
		{
			$val .= str_replace(array('{{pattern}}', '{{message}}', '{{id}}'), array($el->validate->pattern, trans::__($el->validate->formatMessage), $el->id), $this->validation['Format']);
		}
		if (! empty($el->validate->Email) && isset($this->validation['Email']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}'), array(trans::__($el->validate->Email), $el->id), $this->validation['Email']);
		}
		if (! empty($el->validate->Presence) && $el->validate->Presence && isset($this->validation['Presence']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}'), array(trans::__($el->validate->presenceMessage), $el->id), $this->validation['Presence']);
		}
		if (! empty($el->validate->Length) && $el->validate->Length && isset($this->validation['Length']))
		{
			$x = str_replace(array('{{message}}', '{{minLength}}', '{{maxLength}}', '{{id}}'), array(trans::__($el->validate->lengthMessage), $el->validate->minLength, $el->validate->maxLength, $el->id), $this->validation['Length']);
			$x = str_replace(array(' minLength=""', ' maxLength=""', ' minimum: ""', ' maximum: ""'), '', $x);
			if (false !== (strpos($x, 'minimum:')) && false !== (strpos($x, 'maximum:')))
				$x = str_replace('maximum', ', maximum', $x);
			$val .= $x ;
		}
		if (! empty($el->validate->Numericality) && $el->validate->Numericality && isset($this->validation['Numericality']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}', '{{maxValue}}', '{{minValue}}', '{{onlyInteger}}'), array(trans::__($el->validate->numberMessage), $el->id, $el->validate->numberMaximum, $el->validate->numberMinimum, $el->validate->onlyInteger), $this->validation['Numericality']);
			$val = str_replace(array('  ', ' minimum: ,', ', maximum: }'), array(' ', '', '}'), $val);
			if (! empty($el->validate->numberMaximum) && isset($this->validation['lessThan']) )
			{
				$val .= str_replace(array('{{message}}', '{{maxValue}}', '{{id}}'), array(trans::__($el->validate->numberMessageMaximum), $el->validate->numberMaximum,  $el->id), $this->validation['lessThan']);
			}
			if (! empty($el->validate->numberMinimum) && isset($this->validation['greaterThan']) )
			{
				$val .= str_replace(array('{{message}}', '{{minValue}}', '{{id}}'), array(trans::__($el->validate->numberMessageMinimum), $el->validate->numberMinimum,  $el->id), $this->validation['greaterThan']);
			}
			if (! empty($el->validate->integer) && $el->validate->integer && isset($this->validation['Integer']) )
			{
				$val .= str_replace(array('{{message}}', '{{id}}'), array(trans::__($el->validate->numberMessageInteger),  $el->id), $this->validation['Integer']);
			}
		}
		if (! empty($el->validate->Confirmation) && $el->validate->Confirmation && isset($this->validation['Confirmation']))
		{
			$val .= str_replace(array('{{message}}', '{{confirm}}', '{{id}}'), array(trans::__($el->validate->messageConfirmation), $el->validate->Confirmation, $el->id), $this->validation['Confirmation']);
		}
		if (! empty($el->validate->pleaseSelect) && $el->validate->pleaseSelect && isset($this->validation['PleaseSelect']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}', '{{extras}}', '{{name}}'), array(trans::__($el->validate->exclusionMessage), $el->id, $el->validate->exclusionExtras, $el->name), $this->validation['PleaseSelect']);
			$el->validate->Exclusion = false ;
		}
		if (! empty($el->validate->Exclusion) && $el->validate->Exclusion && isset($this->validation['Exclusion']))
		{
			$jsonWithin = substr(json_encode(array('within' => $el->validate->within)), 1, -1);
			$val .= str_replace(array('{{message}}', '{{id}}', '{{within}}', '{{jsonwithin}}', '{{extras}}', '{{name}}'), array(trans::__($el->validate->exclusionMessage), $el->id, $el->validate->within, $jsonWithin, $el->validate->exclusionExtras, $el->name), $this->validation['Exclusion']);
		}

		if (! empty($el->validate->Inclusion) && $el->validate->Inclusion && isset($this->validation['Inclusion']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}', '{{within}}', '{{partialMatch}}', '{{caseSensitive}}'), array(trans::__($el->validate->inclusionMessage), $el->id, $el->validate->inclusionWithin,  $el->validate->inclusionPartialMatch, $el->validate->inclusionCaseSensitive), $this->validation['Inclusion']);
		}

		if (! empty($el->validate->File) && $el->validate->File && isset($this->validation['File']))
		{
			$val .= str_replace(array('{{message}}', '{{id}}', '{{within}}', '{{mimeType}}'), array(trans::__($el->validate->inclusionMessage), $el->id, str_replace("'", '', $el->validate->inclusionWithin), $el->validate->mimeType), $this->validation['File']);
		}
		
		
		if ($el->autoComplete) 
		{
			$val .= '
$(function() {
	var availableTags=['.$el->availableTags.'];
	$( "#'.$el->id.'" ).autocomplete({source: availableTags});
});
			';
		}

		return $val; 
	}
	
	/**
	 * set (Constant)
	 *
	 * @version	17th June 2016
	 * @since	17th June 2016
	 * @param	string		$name
	 * @param	mixed		$value
	 * @return	void
	 */
	public function set($name, $value)
	{
		$this->$name = $value ;
		return $this ;
	}

	/**
	 * Set URL
	 *
	 * @version	17th June 2016
	 * @since	17th June 2016
	 * @param	string		$message
	 * @param	string		$protocols
	 * @return	void
	 */
	public function setURL($message = 'Invalid URL!', $protocols = 'http,https')
	{
		$this->getValidate()->URL = true;
		$this->getValidate()->messageURL = $message;
		$this->getValidate()->protocolsURL = $protocols;
	}

	/**
	 * Set Length
	 *
	 * @version	20th June 2016
	 * @since	20th June 2016
	 * @param	integer		$max
	 * @return	void
	 */
	public function setMaxLength($max)
	{
		$this->setLength('Exceeded Maximum allowed Length', null, $max);
	}

	/**
	 * Set Read Only
	 *
	 * @version	25th June 2016
	 * @since	25th June 2016
	 * @param	integer		$max
	 * @return	void
	 */
	public function setReadOnly()
	{
		$this->validateOff() ;
		$this->readOnly = true ;
	}

	/**
	 * Set Disabled
	 *
	 * @version	14th July 2016
	 * @since	14th July 2016
	 * @param	integer		$max
	 * @return	void
	 */
	public function setDisabled()
	{
		$this->validateOff() ;
		$this->disabled = true ;
	}

    /**
     * set ID
     *
	 * @version	15th July 2016
	 * @version	1st July 2016
	 * @param 	string		$name
     * @return	string
     */
    public function setID($name = null)
    {
		if (! is_null($name))
			$this->id = null;
		if (! empty($this->id))
			return $this->id ;
		$this->id = is_null($name) ? '_'.$this->name : '_'.$name;
		$this->id = str_replace(array('[', ']'), array('_', ''), $this->id);
		return $this->id ;
    }
 
    /**
     * merge Class
     *
	 * @version	9th July 2016
	 * @version	9th July 2016
     * @return	string
     */
    public function mergeClass()
    {
		$x = array('row');
		foreach($x as $w)
		{
			if (! empty($this->$w->mergeClass) && ! empty($this->$w->class))
			{
				$c = array_merge(explode(' ', $this->$w->class), explode(' ', $this->$w->mergeClass));
				$this->$w->class = implode(' ', $c);
			}
			elseif (! empty($this->$w->mergeClass) && empty($this->$w->class))
				$this->$w->class = $this->$w->mergeClass; 
		}
    }
 
    /**
     * set Element Only
     *
	 * @version	9th July 2016
	 * @version	9th July 2016
     * @return	string
     */
    public function setElementOnly()
    {
		$this->elementOnly = true ;
    }
 
    /**
     * set Form ID
     *
	 * @version	9th July 2016
	 * @version	9th July 2016
     * @return	string
     */
    public function setFormID($name = 'TheForm')
    {
		$this->formID = $name ;
    }

    /**
     * set Auto Complete
     *
	 * @version	10th August 2016
	 * @since	10th August 2016
	 * @param	array		$tags
     * @return	void
     */
    public function setAutoComplete(array $tags)
    {
		$avail = '';
		foreach($tags as $tag)
			$avail .= '"'.$tag.'",';
		$this->availableTags = rtrim($avail, ',');
		$this->autoComplete = true ;
    }
}
