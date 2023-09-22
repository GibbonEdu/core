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
use Gibbon\Forms\Element;

/**
 * Scanner
 *
 * @version v23
 * @since   v23
 */
class Scanner extends TextField
{       
    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        global $page;

        $page->scripts->add('instascan');
        
        $output = '<div class="input-box border-0 standardWidth">';
        $output .= '<input type="text" '.$this->getAttributeString().'>';
        $output .= '<div class="inline-button -ml-px border border-l-0 rounded-r-sm text-base text-gray-600" style="border-left: 0px; height: 36px;" onclick="scanner(this)">
        
        <svg class="w-4 h-4 mt-1 text-gray-800 fill-current" enable-background="new 0 0 512 512" height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><g><path d="m40 40h80v-40h-120v120h40z"/><path d="m392 0v40h80v80h40v-120z"/><path d="m40 392h-40v120h120v-40h-80z"/><path d="m472 472h-80v40h120v-120h-40z"/><path d="m76 236h160v-160h-160zm40-120h80v80h-80z"/><path d="m436 76h-160v160h160zm-40 120h-80v-80h80z"/><path d="m76 436h160v-160h-160zm40-120h80v80h-80z"/><path d="m316 316v-40h-40v80h40v40h40v-80z"/><path d="m356 396h80v40h-80z"/><path d="m396 356h40v-80h-80v40h40z"/><path d="m276 396h40v40h-40z"/></g></svg>
        
        </div>';
        $output .= '</div>';

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
