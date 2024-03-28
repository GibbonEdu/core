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

/**
 * Editor - Rich text
 *
 * @version v14
 * @since   v14
 */
class Editor extends Input
{
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
     * Set the textarea rows attribute to control the height of the editor box.
     * @param  int  $count
     * @return $this
     */
    public function setRows($count)
    {
        $this->rows = $count;
        return $this;
    }

    /**
     * Set whether tinyMCE uploader should be enabled.
     *
     * @param   bool  $value
     * @return  $this
     */
    public function tinymceInit(bool $value)
    {
        $this->tinymceInit = $value;
        return $this;
    }

    /**
     * Set whether the media bar for upload and quick inser is available.
     * @param   bool    $value
     * @return  $this
     */
    public function showMedia($value = true)
    {
        $this->showMedia = $value;
        return $this;
    }

    /**
     * Set whether the editor input is initially hidden.
     * @param   bool    $value
     * @return  $this
     */
    public function initiallyHidden($value = true)
    {
        $this->initiallyHidden = $value;
        return $this;
    }

    /**
     * Allow resources to be uploaded through the editor window.
     * @param   bool    $value
     * @return  $this
     */
    public function allowUpload($value = true)
    {
        $this->allowUpload = $value;
        return $this;
    }

    /**
     * Sets the sort order for resource upload.
     * @param   bool    $value
     * @return  $this
     */
    public function resourceAlphaSort($value = true)
    {
        $this->resourceAlphaSort = $value;
        return $this;
    }

    /**
     * Add a javascript function to the form's onkeydown event.
     * @param string $function
     * @return self
     */
    public function enableAutoSave(string $url, string $formId)
    {
        $this->onKeyDownSubmitUrl = $url;
        $this->onKeyDownSubmitFormId = $formId;
        return $this;
    }

    /**
     * Sets a filter for resource upload.
     * @param   string    $value
     * @return  $this
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
    protected function getElement(): string
    {
        if ($this->getReadonly()) {
            return '<p>'.$this->getValue().'</p>';
        } else {
            /**
             * @var \Gibbon\View\Page $page
             * @var \Gibbon\Contracts\Services\Session $session
             */
            global $page, $session;
            $templateData = [
                'tinymceInit' => $this->tinymceInit,
                'name' => $this->getName(),
                'id' => preg_replace('/[^a-zA-Z0-9_-]/', '', ($this->getID() ?: $this->getName())),
                'value' => $this->getValue(),
                'rows' => $this->rows,
                'showMedia' => $this->showMedia,
                'required' => $this->getRequired(),
                'initiallyHidden' => $this->initiallyHidden,
                'allowUpload' => $this->allowUpload,
                'initialFilter' => $this->initialFilter,
                'resourceAlphaSort' => $this->resourceAlphaSort,
                'absoluteURL' => $session->get('absoluteURL'),
                'onKeyDownSubmitUrl' => $this->onKeyDownSubmitUrl,
                'onKeyDownSubmitFormId' => $this->onKeyDownSubmitFormId,
            ];

            return $page->fetchFromTemplate('components/editor.twig.html', $templateData);
        }
    }
}
