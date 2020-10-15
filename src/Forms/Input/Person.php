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
use Gibbon\Contracts\Database\Connection;

/**
 * Person
 *
 * @version v18
 * @since   v18
 */
class Person extends Select
{
    protected $displayPhoto = true;
    protected $size = 'large';

    public function photo($value, $size = 'large')
    {
        $this->displayPhoto = $value;
        $this->size = $size;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $this->addClass('personSelect');

        $output = '';
        $output .= '<div class="flex-1 flex justify-end items-center '.$this->getClass().' '.($this->size == 'large' ? 'pl-24' : 'pl-12').' lg:pl-0">';
        if ($this->displayPhoto) {

            $output .= '<div id="'.$this->getID().'Photo" class="flex-none relative '.($this->size == 'large' ? 'w-20 h-20' : 'w-10 h-10').' z-10  mr-4 rounded-full bg-gray-200 border border-solid border-gray-400 bg-no-repeat">';
            $output .= '<div id="'.$this->getID().'Count" class="hidden badge"></div>';
            $output .= '</div>';

            $output .= '<script>
            $(function(){
                $("#'.$this->getID().'").on("input", function() {
                    var value =  $(this).val();

                    if ( Array.isArray(value) && value.length > 1) {
                        value = value.filter(function (value, index, self) { 
                            return self.indexOf(value) === index;
                        });

                        $("#'.$this->getID().'Count").show();
                        $("#'.$this->getID().'Count").html(value.length);
                        $("#'.$this->getID().'Photo")
                            .css("background-image" , "url(./themes/Default/img/attendance_large.png)")
                            .css("background-size", "50px 50px")
                            .css("background-position", "50% 45%");

                        return;
                    } else {
                        $("#'.$this->getID().'Count").hide();
                    }
                    var personID = Array.isArray(value) ? value[0] : value;
                    $.ajax({
                        url: "./modules/User Admin/user_manage_userPhotoAjax.php",
                        data: { gibbonPersonID: personID, },
                        type: "POST",
                        success: function(data) {
                            $("#'.$this->getID().'Count").html("");
                            $("#'.$this->getID().'Photo")
                                .css("background-image" , "url(./"+data+")")
                                .css("background-size", "cover")
                                .css("background-position", "50% 20%");
                        }
                    });
                });

                var value =  $("#'.$this->getID().'").val();
                if (value != "" && value != "Please select...") {
                    $("#'.$this->getID().'").trigger("input");
                }
            });
            </script>';
        }

        $output .= parent::getElement();

        $output .= '</div>';

        return $output;
    }
}
