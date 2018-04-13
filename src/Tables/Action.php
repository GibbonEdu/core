<?php
/*
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

namespace Gibbon\Tables;

/**
 * Action
 *
 * @version v16
 * @since   v16
 */
class Action
{
    protected $name;
    protected $label;
    protected $url;
    protected $icon;
    protected $params = array();

    protected $modal = false;
    protected $displayLabel = false;

    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->setLabel($label);

        switch ($this->name) {
            case 'add':     $this->setIcon('page_new');
                            break;
            case 'edit':    $this->setIcon('config');
                            break;
            case 'delete':  $this->setIcon('garbage')->isModal(true)->addParam('width', '650')->addParam('height', '135');
                            break;
            default:
            case 'view':    $this->setIcon('zoom');
                            break;
        }

    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function displayLabel($value = true)
    {
        $this->displayLabel = $value;

        return $this;
    }

    public function setURL($url)
    {
        $this->url = $url;

        return $this;
    }

    public function getURL()
    {
        return $this->url;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function addParam($name, $value = '')
    {
        $this->params[$name] = $value;

        return $this;
    }

    public function isModal($value = true) 
    {
        $this->modal = $value;

        return $this;
    }

    public function getOutput(&$data = array(), $params = array())
    {
        global $guid;

        $class = '';
        $path = $_SESSION[$guid]['absoluteURL'].'/index.php';
        $queryParams = array();

        if (!empty($data)) {
            foreach (array_merge($params, $this->params) as $key => $value) {
                $queryParams[$key] = (empty($value) && !empty($data[$key]))? $data[$key] : $value;
            }
        }

        if ($this->modal) {
            $path = $_SESSION[$guid]['absoluteURL'].'/fullscreen.php';
            $class = 'thickbox';
        }

        $url = $path.'?q='.$this->getURL();
        if (!empty($queryParams)) {
            $url .= '&'.http_build_query($queryParams);
        }

        return sprintf('<a href="%1$s" class="%2$s">%3$s<img title="%4$s" src="'.$_SESSION[$guid]['absoluteURL'].'/themes/Default/img/%5$s.png" style="margin-left: 5px"></a>', 
            $url, 
            $class,
            ($this->displayLabel? $this->getLabel() : ''),
            $this->getLabel(), 
            $this->getIcon()
        );
    }
}