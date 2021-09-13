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
use Gibbon\Forms\Element;

/**
 * TextField
 *
 * @version v14
 * @since   v14
 */
class Scanner extends Input
{
    protected $autocomplete;
    protected $unique;

    /**
     * Set a max character count for this text field.
     * @param   string  $value
     * @return  self
     */
    public function maxLength($value = '')
    {
        if (!empty($value)) {
            $this->setAttribute('maxlength', $value);
            $this->addValidation('Validate.Length', 'maximum: '.$value);
        }

        return $this;
    }

    /**
     * Set the default text that appears before any text has been entered.
     * @param   string  $value
     * @return  self
     */
    public function placeholder($value = '')
    {
        $this->setAttribute('placeholder', $value);

        return $this;
    }

    /**
     * Enables javascript autocompletion from the supplied set of values.
     * @param   string|array  $value
     * @return  self
     */
    public function autocomplete($value = '')
    {
        $this->autocomplete = (is_array($value))? $value : array($value);
        $this->setAttribute('autocomplete', 'on');

        return $this;
    }

    /**
     * @deprecated Remove setters that start with isXXX for code consistency.
     */
    public function isUnique($ajaxURL, $data = [])
    {
        return $this->uniqueField($ajaxURL, $data);
    }

    /**
     * Add an AJAX uniqueness check to this field using the given URL.
     *
     * @param string $ajaxURL
     * @param array $data
     * @return self
     */
    public function uniqueField($ajaxURL, $data = [])
    {
        $label = $this->row->getElement('label'.$this->getName());
        $fieldLabel = (!empty($label))? $label->getLabelText() : ucfirst($this->getName());

        $this->unique = array(
            'ajaxURL'      => $ajaxURL,
            'ajaxData'     => array_replace(array('fieldName' => $this->getName()), $data),
            'alertSuccess' => sprintf(__('%1$s available'), $fieldLabel),
            'alertFailure' => sprintf(__('%1$s already in use'), $fieldLabel),
            'alertError'   => __('An error has occurred.'),
        );

        return $this;
    }

    /**
     * Adds uniqueness text to the label description (if not already present)
     * @return string|bool
     */
    public function getLabelContext($label)
    {
        if (!empty($this->unique)) {
            return $this->getRequired()
                ? __('Must be unique.')
                : __('Must be unique if set.');
        }

        return false;
    }
    
    
    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        
        
        $output = '<div class="input-box border-0 standardWidth">';
        $output .= '<input type="text" '.$this->getAttributeString().'>';
        $output .= '<div class="inline-button border border-l-0 rounded-r-sm text-base text-gray-600" style="border-left: 0px; height: 36px;" onclick="scanner(this)"><img src="./themes/Default/img/search.png"/></div>';
        $output .= '</div>';
        $output .= '<script type="text/javascript" src="./lib/instascan/instascan.min.js"></script>'; //TODO: IMPLEMENT INTO CORE
        $output .= '<script type="text/javascript">
            function scanner(self) {
                if ($("#preview").length > 0) {
                    document.getElementById("preview").remove();
                    document.getElementById("cameraButton").remove();
                } else {
                    $(self).parent().parent().append(\'<video id="preview" class="standardWidth"></video>\');
                }
                let scanner = new Instascan.Scanner({ video: document.getElementById("preview") });
                  scanner.addListener("scan", function (content) {
                    scanner.stop()
                    $("input", $(self).parent()).val(content);
                    document.getElementById("preview").remove()
                    document.getElementById("cameraButton").remove();
                  });
                  Instascan.Camera.getCameras().then(function (cameras) {
                    count = 0;
                    if (cameras.length > 0) {
                      scanner.start(cameras[count]);
                      if (cameras.length > 1) {
                        if ($("#cameraButton").length < 1 && $("#preview").length > 0) {
                            $(self).parent().parent().append(\'<button type="button" class="button border rounded-r-sm text-base text-gray-600" id="cameraButton" style="height: 36px;">Change Camera</button>\');
                        }
                        $("#cameraButton").on("click", function(){
                            count++;
                            if (count > cameras.length) {
                                count = 0;
                            }
                            scanner.start(cameras[count]);
                        });
                      }
                    } else {
                      scanner.stop()
                      $("input", $(self).parent()).val("No camera available");
                    }
                  }).catch(function (e) {
                    $("input", $(self).parent()).val("Camera Error");
                  });   
            }
        </script>';
       
        
        if (!empty($this->autocomplete)) {
            $source = implode(',', array_map(function ($str) { return sprintf('"%s"', $str); }, $this->autocomplete));
            $output .= '<script type="text/javascript">';
            $output .= '$("#'.$this->getID().'").autocomplete({source: ['.$source.']});';
            $output .= '</script>';
        }

        if (!empty($this->unique)) {
            $output .= '<script type="text/javascript">
                $("#'.$this->getID().'").gibbonUniquenessCheck('.json_encode($this->unique).');
            </script>';
        }

        return $output;
    }
}
