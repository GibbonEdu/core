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
    protected $accepts = array();
    protected $absoluteURL = '';
    protected $deleteAction = '';

    public function accepts($accepts)
    {
        if (is_string($accepts)) {
            $accepts = explode(',', $accepts);
        }

        if (!empty($accepts) && is_array($accepts)) {

            $within = implode(',', array_map(function ($str) {
                return sprintf("'.%s'", trim($str, " .'")); },
            $accepts));

            $this->setAttribute('accept', str_replace("'",'', $within));
            $this->addValidation('Validate.Inclusion', 'within: ['.$within.'], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false');
        }
        return $this;
    }

    public function setAttachment($absoluteURL, $filePath)
    {
        $this->absoluteURL = $absoluteURL;
        $this->setValue($filePath);

        return $this;
    }

    public function setDeleteAction($actionURL)
    {
        $this->deleteAction = ltrim($actionURL, '/');

        return $this;
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->absoluteURL) && !empty($this->getValue())) {
            $output .= '<div class="standardWidth" style="float: right;border: 1px solid #BFBFBF;background-color: #ffffff;margin-bottom:4px;height:48px;overflow:hidden;display: table;">';

            $output .= '<div style="display:table-cell;white-space:no-wrap;text-align:left;padding: 5px;">';
            $output .= __('Current attachment:').'<br/>';
            $output .= '<a target="_blank" style="display:block; word-break: break-all;" href="'.$this->absoluteURL.'/'.$this->getValue().'">'.$this->getValue().'</a>';
            $output .= '</div>';

            $output .=  "<a download style='display:table-cell;border-left: 1px solid #BFBFBF;background: -moz-linear-gradient(top, #fbfbfb, #fafafa);height: 48px; width:48px;vertical-align:middle;text-align:center;' href='".$this->absoluteURL.'/'.$this->getValue()."'><img title='".__('Download')."' src='./themes/Default/img/download.png'/></a>";

            if (!empty($this->deleteAction)) {
                $output .=  "<a style='display:table-cell;border-left: 1px solid #BFBFBF;background: -moz-linear-gradient(top, #fbfbfb, #fafafa);height: 48px; width:48px;vertical-align:middle;text-align:center;' href='".$this->absoluteURL.'/'.$this->deleteAction."' onclick='return confirm(\"Are you sure you want to delete this record? Unsaved changes will be lost.\")'><img title='".__('Delete')."' src='./themes/Default/img/garbage.png'/></a>";
            }
            $output .= '</div>';
        }

        $output .= '<input type="file" '.$this->getAttributeString().'>';

        return $output;
    }
}
