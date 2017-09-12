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
 * TextField
 *
 * @version v14
 * @since   v14
 */
class FileUpload extends Input
{
    protected $absoluteURL = '';
    protected $deleteAction = '';

    protected $attachmentName;
    protected $attachmentPath;
    protected $canDelete = true;

    /**
     * Set an array or CSV string of file extensions accepted by this file input.
     * @param   array|string  $accepts
     * @return  self
     */
    public function accepts($accepts)
    {
        if (is_string($accepts)) {
            $accepts = explode(',', $accepts);
        }

        if (!empty($accepts) && is_array($accepts)) {
            $accepts = array_map(function ($str) {
                return trim(strtolower($str), " .'");
            }, $accepts);

            $within = implode(',', array_map(function ($str) {
                return sprintf("'.%s'", $str);
            }, $accepts));

            $this->setAttribute('title', (count($accepts) < 20? implode(', ', $accepts) : ''));
            $this->setAttribute('accept', str_replace("'", '', $within));
            $this->addValidation('Validate.Inclusion', 'within: ['.$within.'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false');
        }
        return $this;
    }

    /**
     * Set the attachment name and path.
     * @param  string $name
     * @param  string  $absoluteURL
     * @param  string  $filePath
     */
    public function setAttachment($name, $absoluteURL, $filePath = '')
    {
        $this->absoluteURL = $absoluteURL;
        $this->attachmentName = $name;
        $this->attachmentPath = $filePath;

        return $this;
    }

    /**
     * Set the URL to visit if the delete action is clicked.
     * @param  string  $actionURL
     */
    public function setDeleteAction($actionURL)
    {
        $this->deleteAction = ltrim($actionURL, '/');

        return $this->canDelete(true);
    }

    /**
     * Sets whether the attachment will have a delete option.
     * @param   bool  $value
     * @return  self
     */
    public function canDelete($value)
    {
        $this->canDelete = $value;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        if (!empty($this->absoluteURL) && !empty($this->attachmentPath)) {
            $output .= '<div class="input-box standardWidth">';

            $output .= '<div class="inline-label">';
            $output .= __('Current attachment:').'<br/>';
            $output .= '<a target="_blank" href="'.$this->absoluteURL.'/'.$this->attachmentPath.'">'.basename($this->attachmentPath).'</a>';
            $output .= '</div>';

            $output .=  "<a download class='inline-button' href='".$this->absoluteURL.'/'.$this->attachmentPath."'><img title='".__('Download')."' src='./themes/Default/img/download.png'/></a>";

            if ($this->canDelete) {
                if (!empty($this->deleteAction)) {
                    $output .=  "<a class='inline-button' href='".$this->absoluteURL.'/'.$this->deleteAction."' onclick='return confirm(\"".__('Are you sure you want to delete this record?').' '.__('Unsaved changes will be lost.')."\")'><img title='".__('Delete')."' src='./themes/Default/img/garbage.png'/></a>";
                } else {
                    $output .= "<div class='inline-button' onclick='if(confirm(\"".__('Are you sure you want to delete this record?').' '.__('Changes will be saved when you submit this form.')."\")) { $(\"input[name=".$this->attachmentName."]\").val(\"\"); $(\"#".$this->getID()."\").show(); $(this).parent().detach().remove(); };'><img title='".__('Delete')."' src='./themes/Default/img/garbage.png'/></div>";
                }
            }
            $output .= '</div>';

            $this->setAttribute('style', 'display:none;');
        }

        $output .= '<input type="hidden" name="'.$this->attachmentName.'" value="'.$this->attachmentPath.'">';
        $output .= '<input type="file" '.$this->getAttributeString().'>';

        return $output;
    }
}
