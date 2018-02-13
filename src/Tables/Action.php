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

    public function __construct($name, $label = '')
    {
        $this->name = $name;
        $this->setLabel($label);

        switch ($this->name) {
            case 'edit':    $this->setIcon('config');
                            break;
            case 'delete':  $this->setIcon('garbage')->isModal(true)->addParam('width', '650')->addParam('height', '135');
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

    public function getContents(&$data)
    {
        global $guid;

        $class = '';
        $path = $_SESSION[$guid]['absoluteURL'].'/index.php';
        $params = array();

        if (!empty($this->params)) {
            foreach ($this->params as $key => $value) {
                $params[$key] = (empty($value) && !empty($data[$key]))? $data[$key] : $value;
            }
        }

        if ($this->modal) {
            $path = $_SESSION[$guid]['absoluteURL'].'/fullscreen.php';
            $class = 'thickbox';
        }

        $url = $path.'?q='.$this->getURL();
        if (!empty($params)) {
            $url .= '&'.http_build_query($params);
        }

        return sprintf(' <a href="%1$s" class="%2$s"><img title="%3$s" src="./themes/Default/img/%4$s.png"></a>', 
            $url, 
            $class,
            $this->getLabel(), 
            $this->getIcon()
        );
    }
}