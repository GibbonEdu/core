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

use Gibbon\View\Component;

/**
 * Editor - Rich text
 *
 * @version v14
 * @since   v14
 */
class Editor extends Input
{
    protected $mode = 'full';
    protected $tinymceInit = true;
    protected $rows = 20;
    protected $showMedia = false;
    protected $initiallyHidden = false;
    protected $allowUpload = true;
    protected $resourceAlphaSort = false;
    protected $initialFilter = '';
    protected $onKeyDownSubmitUrl = '';
    protected $onKeyDownSubmitFormId = '';

    /**
     * Create a tinyMCE rich-text editor input.
     * @param  string  $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Sets the TinyMCE editor display mode.
     * Options: full, inline, minimal
     *
     * @param string $mode
     * @return self
     */
    public function setMode(string $mode) : Editor
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Set the textarea rows attribute to control the height of the editor box.
     * @param  int  $count
     * @return $this
     */
    public function setRows($count) : Editor
    {
        $this->rows = $count;
        return $this;
    }

    /**
     * Set whether tinyMCE uploader should be enabled.
     *
     * @param   bool  $value
     * @return  self
     */
    public function tinymceInit(bool $value) : Editor
    {
        $this->tinymceInit = $value;
        return $this;
    }

    /**
     * Set whether the media bar for upload and quick inser is available.
     * @param   bool    $value
     * @return  self
     */
    public function showMedia($value = true) : Editor
    {
        $this->showMedia = $value;
        return $this;
    }

    /**
     * Set whether the editor input is initially hidden.
     * @param   bool    $value
     * @return  self
     */
    public function initiallyHidden($value = true) : Editor
    {
        $this->initiallyHidden = $value;
        return $this;
    }

    /**
     * Allow resources to be uploaded through the editor window.
     * @param   bool    $value
     * @return  self
     */
    public function allowUpload($value = true) : Editor
    {
        $this->allowUpload = $value;
        return $this;
    }

    /**
     * Sets the sort order for resource upload.
     * @param   bool    $value
     * @return  self
     */
    public function resourceAlphaSort($value = true) : Editor
    {
        $this->resourceAlphaSort = $value;
        return $this;
    }

    /**
     * Add a javascript function to the form's onkeydown event.
     * @param string $function
     * @return self
     */
    public function enableAutoSave(string $url, string $formId) : Editor
    {
        $this->onKeyDownSubmitUrl = $url;
        $this->onKeyDownSubmitFormId = $formId;
        return $this;
    }

    /**
     * Sets a filter for resource upload.
     * @param   string    $value
     * @return  self
     */
    public function initialFilter($value = '') : Editor
    {
        $this->initialFilter = $value;
        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement(): string 
    {
        return Component::render(Editor::class, [
            'id'                    => preg_replace('/[^a-zA-Z0-9_-]/', '', $this->getName()),
            'mode'                  => $this->mode,
            'rows'                  => $this->rows,
            'showMedia'             => $this->showMedia,
            'required'              => $this->getRequired(),
            'allowUpload'           => $this->allowUpload,
            'onKeyDownSubmitUrl'    => $this->onKeyDownSubmitUrl,
            'onKeyDownSubmitFormId' => $this->onKeyDownSubmitFormId,
        ] + $this->getAttributeArray());
    }
}
