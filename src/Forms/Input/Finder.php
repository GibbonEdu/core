<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
    protected $resultsFormatter = null;
    protected $tokenFormatter = null;

    /**
     * Create a finder with default params.
     * @param  string  $name
     */
    public function __construct($name)
    {
        $this->params = array(
            'theme'             => 'facebook',
            'hintText'          => __('Start typing...'),
            'noResultsText'     => __('No results'),
            'searchingText'     => __('Searching...'),
            'allowFreeTagging'  => false,
            'preventDuplicates' => true,
            'tokenLimit'        => null,
            'minChars'          => 1,
            'resultsLimit'      => null,
            'enableHTML'        => true,
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
     * @param   mixed  $values
     * @return  self
     */
    public function selected($values)
    {
        if (is_string($values) && $values != '') {
            $values = stripslashes(str_replace('\\\\', '', $values));
            $values = explode(',', $values);
        }

        if (!empty($values) && is_array($values)) {
            if (array_values($values) === $values) {
                $this->selected = array_combine($values, $values);
            } else {
                $this->selected = $values;
            }
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
     * Adds a javascript function as a string to replace the default Results Formatter.
     *
     * @param string $value
     * @return self
     */
    public function resultsFormatter($value)
    {
        $this->resultsFormatter = $value;
        return $this;
    }

    /**
     * Adds a javascript function as a string to replace the default Token Formatter.
     *
     * @param string $value
     * @return self
     */
    public function tokenFormatter($value)
    {
        $this->tokenFormatter = $value;
        return $this;
    }

    /**
     * Returns a $key => $value array formatted as an {id: ..., name: ...} token array.
     * @param    array  $items
     * @return   string
     */
    protected function getTokenizedList($items)
    {
        if (!is_array($items)) $items = [$items];

        return array_map(function ($key, $value) {
            $key = stripslashes($key);
            $value = is_array($value) ? array_map('stripslashes', $value) : stripslashes($value);

            $token = ['id' => $key];
            $value = is_array($value)? $value : ['name' => $value];

            return array_merge($token, $value);
        }, array_keys($items), $items);
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {

        $this->addClass('finderInput');
        $output = '<input type="text" '.$this->getAttributeString().'>';

        $output .= '<script type="text/javascript">';
        $output .= '$(document).ready(function() {';
        $output .= '$("#'.$this->getID().'").tokenInput(';

        if (!empty($this->ajaxURL)) {
            $output .= '"'.$this->ajaxURL.'",';
        } else {
            $output .= json_encode($this->getTokenizedList($this->options)).',';
        }

        // Add the pre-populate param if there's selected items
        if (!empty($this->selected)) {
            $this->params['prePopulate'] = $this->getTokenizedList($this->selected);
        }

        // Add the string placeholders for custom functions
        if (!empty($this->resultsFormatter)) {
            $this->params['resultsFormatter'] = 'CUSTOM_RESULTS_FORMATTER';
        }
        if (!empty($this->tokenFormatter)) {
            $this->params['tokenFormatter'] = 'CUSTOM_TOKEN_FORMATTER';
        }
        // Account for the change in param name from allowCreation to allowFreeTagging
        if (!empty($this->params['allowCreation'])) {
            $this->params['allowFreeTagging'] = $this->params['allowCreation'];
        }

        $paramsOutput = json_encode($this->params);

        // Replace the string placeholders with javascript functions - workaround for json string encoding
        if (!empty($this->resultsFormatter) || !empty($this->tokenFormatter)) {
            $paramsOutput = str_replace(
                ['"CUSTOM_RESULTS_FORMATTER"', '"CUSTOM_TOKEN_FORMATTER"'],
                [$this->resultsFormatter, $this->tokenFormatter],
                $paramsOutput
            );
        }

        $output .= $paramsOutput;
        $output .= ');';
        $output .= '});';
        $output .= '</script>';

        return $output;
    }
}
