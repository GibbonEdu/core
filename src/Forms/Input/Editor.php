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

/**
 * Editor - Rich text
 *
 * @version v14
 * @since   v14
 */
class Editor extends Input
{
    protected $guid;
    protected $rows = 20;
    protected $showMedia = false;
    protected $initiallyHidden = false;
    protected $allowUpload = true;
    protected $resourceAlphaSort = false;
    protected $initialFilter = '';

    /**
     * Create a tinyMCE rich-text editor input.
     * @param  string  $name
     * @param  string  $guid
     */
    public function __construct($name, $guid)
    {
        $this->setName($name);
        $this->guid = $guid;
    }

    /**
     * Set the textarea rows attribute to control the height of the editor box.
     * @param  int  $count
     * @return self
     */
    public function setRows($count)
    {
        $this->rows = $count;
    }

    /**
     * Set whether the media bar for upload and quick inser is available.
     * @param   bool    $value
     * @return  self
     */
    public function showMedia($value = true)
    {
        $this->showMedia = $value;
        return $this;
    }

    /**
     * Set whether the editor input is initially hidden.
     * @param   bool    $value
     * @return  [type]
     */
    public function initiallyHidden($value = true)
    {
        $this->initiallyHidden = $value;
        return $this;
    }

    /**
     * Allow resources to be uploaded through the editor window.
     * @param   bool    $value
     * @return  self
     */
    public function allowUpload($value = true)
    {
        $this->allowUpload = $value;
        return $this;
    }

    /**
     * Sets the sort order for resource upload.
     * @param   bool    $value
     * @return  self
     */
    public function resourceAlphaSort($value = true)
    {
        $this->resourceAlphaSort = $value;
        return $this;
    }

    /**
     * Sets a filter for resource upload.
     * @param   string    $value
     * @return  self
     */
    public function initialFilter($value = '')
    {
        $this->initialFilter = $value;
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        return getEditor($this->guid, true, $this->getName(), $this->getValue(), $this->rows, $this->showMedia, $this->getRequired(), $this->initiallyHidden, $this->allowUpload, $this->initialFilter, $this->resourceAlphaSort);
    }
}
