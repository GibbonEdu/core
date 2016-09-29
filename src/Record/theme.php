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
namespace Gibbon\Record ;

use Symfony\Component\Yaml\Yaml ;
use Gibbon\core\view ;

/**
 * Theme Record
 *
 * @version	23rd September 2016
 * @since	5th May 2016
 * @author	Craig Rayner
 * @package		Gibbon
 * @subpackage	Record
 */
class theme extends record
{
	/** 
	 * @var	string	$table	Table Name
	 */
	protected $table = 'gibbonTheme';
	
	/**
	 * @var	string	$identifier	Table Identifier Name
	 */
	protected $identifier = 'gibbonThemeID';
	
	/**
	 * Unique Test
	 *
	 * @version	5th May 2016
	 * @since	5th May 2016
	 * @return	boolean
	 */
	public function uniqueTest()
	{
		return true ;
	}
	
	/**
	 * Default Record
	 *
	 * @version	13th May 2016
	 * @since	13th May 2016
	 * @return	boolean
	 */
	public function defaultRecord()
	{
		parent::defaultRecord();
		$this->record->version = '';
	}

	/**
	 * can Delete
	 *
	 * @version	26th May 2016
	 * @since	26th May 2016
	 * @return	boolean		
	 */
	public function canDelete()
	{
		return true;
	}

	/**
	 * set Session
	 *
	 * @version	28th August 2016
	 * @since	1st June 2016
	 * @return	void		
	 */
	private function setSessionTheme()
	{
		if (isset($this->record->gibbonThemeID) && intval($this->record->gibbonThemeID) > 0)
		{
			$this->session->set("gibbonThemeID", $this->record->gibbonThemeID) ;
			$this->session->set("gibbonThemeName", $this->record->name) ;
			$this->session->set("gibbonThemeAuthor", $this->record->author) ;
			$this->session->set("gibbonThemeURL", $this->record->url) ;

			$this->session->set("theme.ID", $this->record->gibbonThemeID) ;
			$this->session->set("theme.Name", $this->record->name) ;
			$this->session->set("theme.Author.name", $this->record->author) ;
			$this->session->set("theme.Author.URL", $this->record->url) ;
			$this->session->set("theme.path", GIBBON_ROOT.'src/themes/'.$this->record->name.'/') ;
			$this->session->set("theme.url", GIBBON_URL.'src/themes/'.$this->record->name.'/') ;
			$this->session->set("theme.defaultPath", GIBBON_ROOT.'src/themes/Default/') ;
			$this->session->set("theme.defaultURL", GIBBON_URL.'src/themes/Default/') ;
			$this->session->clear('theme.settings');
			if (file_exists($this->session->get('theme.path').'settings.yml'))
				$this->session->set('theme.settings', Yaml::parse(file_get_contents($this->session->get('theme.path').'settings.yml')));
			elseif (file_exists($this->session->get('theme.defaultPath').'settings.yml'))
			{
				$this->session->set('theme.settings', Yaml::parse(file_get_contents($this->session->get('theme.defaultPath').'settings.yml')));
				$this->session->set("theme.path", GIBBON_ROOT.'src/themes/Default/') ;
				$this->session->set("theme.url", GIBBON_URL.'src/themes/Default/') ;
			}
		}
		else
		{
			$this->session->set("gibbonThemeID", 0001) ;
			$this->session->set("gibbonThemeName", 'Bootstrap') ;
			$this->session->set("gibbonThemeAuthor", 'Craig Rayner') ;
			$this->session->set("gibbonThemeURL", 'http://www.craigrayner.com') ;

			$this->session->set("theme.ID", '0001') ;
			$this->session->set("theme.Name", 'Bootstrap') ;
			$this->session->set("theme.Author.name", 'Craig Rayner') ;
			$this->session->set("theme.Author.URL", 'http://www.craigrayner.com') ;
			$this->session->set("theme.path", GIBBON_ROOT.'src/themes/Bootstrap/') ;
			$this->session->set("theme.url", GIBBON_URL.'src/themes/Bootstrap/') ;
			$this->session->set("theme.defaultPath", GIBBON_ROOT.'src/themes/Bootstrap/') ;
			$this->session->set("theme.defaultURL", GIBBON_URL.'src/themes/Bootstrap/') ;
			$this->session->clear('theme.settings');
			if (file_exists($this->session->get('theme.path').'settings.yml'))
				$this->session->set('theme.settings', Yaml::parse(file_get_contents($this->session->get('theme.path').'settings.yml')));
		}
		if (isset($this->record->name) && in_array($this->record->name, array('Curves')))
		{
			$this->session->set("gibbonThemeID", 0013) ;
			$this->session->set("gibbonThemeName", 'Default') ;
			$this->session->set("gibbonThemeAuthor", 'Ross Parker') ;
			$this->session->set("gibbonThemeURL", 'http://rossparker.org/') ;
		}
	}

	/**
	 * set Default Theme
	 *
	 * @version	28th September 2016
	 * @since	2nd June 2016
	 * @return	void		
	 */
	public function setDefaultTheme()
	{
		$this->findOneBy(array('active' => 'Y'));
		if ($this->session->notEmpty('theme.IDPersonal') && $this->record->gibbonThemeID != $this->session->get('theme.IDPersonal'))
		{
			$this->find($this->session->get('theme.IDPersonal'));
		}
		$this->setSessionTheme();
	}

	/**
	 * Group Check
	 *
	 * @version	18th July 2016
	 * @since	18th July 2016
	 * @param	string		$address
	 * @param	array		$allow
	 * @return	boolean		
	 */
	private function groupCheck($address, $allow)
	{
		do {
			$address = substr($address, 0, -1);
			if (in_array($address, $allow))
				return true ;
		} while (strlen($address) > 0);
		return false ;
	}

	/**
	 * Construct
	 *
	 * @version	11th September 2016
	 * @since	11th September 2016
	 * @param	view		$view
	 * @param	integer		$id 
	 * @return	void
	 */
	public function __construct(view $view, $id = 0)
	{
		parent::__construct($view, $id);
		if ($this->session->isEmpty('theme.tested') || ! $this->session->get('theme.tested'))
		{
			$this->findOneBy(array('name'=>'Bootstrap'));
			if ($this->getSuccess() && $this->rowCount < 1)
			{
				$this->defaultRecord();
				$this->setField('gibbonThemeID', 1);
				$this->setField('name', 'Bootstrap');
				$this->setField('description', "Gibbon's 2016 look and feel.");
				$this->setField('active', 'N');
				$this->setField('version', '1.0.00');
				$this->setField('author', 'Craig Rayner');
				$this->setField('url', 'http://www.craigrayner.com');
				$this->writeRecord(array(), true);
			}
			$this->findOneBy(array('name'=>'Default'));
			if ($this->getSuccess() && $this->rowCount < 1)
			{
				$this->defaultRecord();
				$this->setField('gibbonThemeID', 13);
				$this->setField('name', 'Default');
				$this->setField('description', "Gibbon's 2015 look and feel.");
				$this->setField('active', 'Y');
				$this->setField('version', '1.0.00');
				$this->setField('author', 'Ross Parker');
				$this->setField('url', 'http://rossparker.org');
				$this->writeRecord(array(), true);
			}
		}
		return parent::__construct($view, $id);
	}
}
