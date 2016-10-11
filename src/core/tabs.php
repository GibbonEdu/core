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
namespace Gibbon\core;

/**
 * Tabs
 *
 * @version	10th October 2016
 * @since	10th October 2016
 * @author	Craig Rayner
 * @package	Gibbon
 * @subpackage	Core
 */
class tabs
{
	/**
	 * @var	Gibbon\core\view	
	 */
	private $view ;
	/**
	 * @var	integer	
	 */
	private $tabCount ;

	/**
	 * @var	array
	 */
	private $tabDetails ; 

	/**
	 * Constructor
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	Gibbon\core\view		$view
	 * @return	void
	 */
	public function __construct(view $view)
	{
		$this->view = $view;
		$this->tabDetails = array();
	}

	/**
	 * start Tab
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	integer			$personID
	 * @return	void
	 */
	private function startTab($id)
	{
		$return = "<div id='".$id."tabs' style='margin: 0 0'>\n";
		$return .= "<ul>\n";
		return $return ;
	}

	/**
	 * end Tab
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @return	void
	 */
	private function endTab()
	{
		return "</ul>\n" ;
	}

	/**
	 * end Tab
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	string			$content
	 * @param	string			$header
	 * @return	void
	 */
	public function addTab($content, $header)
	{
		$this->tabDetails[$header] = $content ;
	}

	/**
	 * render Tabs
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	integer			$personID
	 * @param	string			$tabName
	 * @return	void
	 */
	public function renderTabs($id, $tabName)
	{
		$return = $this->startTab($id);
		$this->tabCount = 0;
		$defaultTab = 0;
		foreach($this->tabDetails as $name=>$content)
		{
			$this->tabCount++ ;
			$return .= $this->tabHeader($name);
			if ($name == $tabName)
				$defaultTab = $this->tabCount - 1 ;
		}
		$return .= "</ul>\n";
		$this->tabCount = 0;
		foreach($this->tabDetails as $content)
		{
			$this->tabCount++;
			$return .= $this->tabContent($content);
		}
		$return .= "</div>\n";
		
		$defaultTab = isset($_GET['tab']) ? $_GET['tab'] : $defaultTab ;
	
$script = '
<script type="text/javascript">
	$(function() {
		$("#'.$id.'tabs").tabs( {
			active: '.$defaultTab.',
			ajaxOptions: {
				error: function( xhr, status, index, anchor ) {
					$(anchor.hash).html("Couldn\'t load this tab.");
				}
			}
		});
	});
</script>
';
		$return .= $script ;		

		return $return ;
	}

	/**
	 * tab Header
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	string			$name
	 * @return	void
	 */
	private function tabHeader($name)
	{
		return "<li>
			<a href='#tabs".$this->tabCount."'>".$name."</a>
		</li>\n";
	}

	/**
	 * tab Content
	 *
     * @version	10th October 2016
	 * @since	10th October 2016
	 * @param	string			$content
	 * @return	void
	 */
	private function tabContent($content)
	{
		return "<div id='tabs".$this->tabCount."' style='min-height: 100px; '>
			".$content."
			</div>\n";
	}
}

