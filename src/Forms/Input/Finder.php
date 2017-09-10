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

namespace Gibbon\Forms\Input;

use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * Finder - tokenized search
 *
 * @version v14
 * @since   v14
 */
class Finder extends TextField
{
    use MultipleOptionsTrait;

    protected $params = array();
    protected $selected = null;
    protected $ajaxURL = null;

    /**
     * Create a finder with default params.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->params = array(
            'theme'             => 'facebook',
            'hintText'          => __('Start typing a name...'),
            'noResultsText'     => __('No results'),
            'searchingText'     => __('Searching...'),
            'allowCreation'     => false,
            'preventDuplicates' => true,
            'tokenLimit'        => null,
        );

        parent::__construct($name);
    }

    /**
     * Load tokens from an AJAX url
     * @param    string  $url
     * @return   self
     */
    public function fromAjax($url)
    {
        $this->ajaxURL = $url;
        return $this;
    }

    /**
     * Sets the selected element(s) of the token list.
     * @param   mixed  $value
     * @return  self
     */
    public function selected($value)
    {
        if (is_string($value)) $value = explode(',', $value);

        if (!empty($value)) {
            $this->selected = array_combine($value, $value);
        }
        return $this;
    }

    /**
     * Sets a javascript parameter for the tokenInput UI.
     * @param    string  $key
     * @param    string  $value
     */
    public function setParameter($key, $value)
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Returns a $key => $value array formatted as an {id: ..., name: ...} token csv list.
     * @param    array  $items
     * @return   string
     */
    protected function getTokenizedList($items)
    {
        if (!is_array($items)) $items = array($items);

        $list = array_map(function($key) use (&$items) {
            return "{id: '".addslashes($key)."', name: '".addslashes($items[$key])."'}";
        }, array_keys($items) );

        return implode(',', $list);
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '<input type="text" '.$this->getAttributeString().'>';

        // TODO: move this css to sass files
        $output .= '<style>';
            $output .= 'ul.token-input-list-facebook { width: 300px; float: right; height: 28px!important; margin-right: 0px; box-sizing: border-box; padding-left: 0px;}';
            $output .= 'div.token-input-dropdown-facebook { width: 300px; z-index: 99999999 }';
        $output .= '</style>';

        $output .= '<script type="text/javascript">';
            $output .= '$(document).ready(function() {';
            $output .= '$("#'.$this->getID().'").tokenInput(';

            if (!empty($this->ajaxURL)) {
                $output .= '"'.$_SESSION[$guid]['absoluteURL'].'/index_fastFinder_ajax.php",';
            } else {
                $output .= '['.$this->getTokenizedList($this->options).'],';
            }

            $params = $this->params;

            // JSONify the parameter list for output
            $paramsList = array_map(function($key) use (&$params) {
                $value = $params[$key];

                if (is_string($value)) $value = '"'.$value.'"';
                if (is_array($value)) $value = '['.implode(',', $value).']';
                if (is_bool($value)) $value = ($value)? 'true' : 'false';
                if (is_null($value)) $value = 'null';

                return $key.': '.$value;
            }, array_keys($params) );

            // Add the pre-populate param if there's selected items
            if (!empty($this->selected)) {
                $paramsList[] = 'prePopulate: ['.$this->getTokenizedList($this->selected).']';
            }

            $output .= '{'.implode(',', $paramsList).'}';
            $output .= ');';
            $output .= '});';
        $output .= '</script>';

        return $output;
    }
}
