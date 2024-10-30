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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\View\View;
use Gibbon\Forms\FormFactory;
use Gibbon\Forms\Input\Input;
use Gibbon\Services\Format;

/**
 * Documents
 *
 * @version v24
 * @since   v24
 */
class Documents extends Input
{
    protected $view;
    protected $factory;
    protected $validation;
    protected $absoluteURL;
    protected $mode;

    protected $documents;
    protected $attachments = [];


    public function __construct(FormFactory &$factory, $name, $documents, View $view, $absoluteURL, $mode)
    {
        $this->view = $view;
        $this->factory = $factory;
        $this->documents = $documents;
        $this->absoluteURL = $absoluteURL;
        $this->mode = $mode;

        $this->setID($name);
        $this->setName($name);
    }

    /**
     * Get the validation output from the internal fields.
     * @return  string
     */
    public function getValidationOutput()
    {
        return $this->validation;
    }

    public function setAttachments(&$attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $name = $this->getName();

        foreach ($this->documents as $index => $document) {
            $output .= '<input type="hidden" name="'.$name.'['.$index.'][id]" value="'.$document.'">';

            $output .= '<div class="document rounded-sm bg-white border font-sans mt-4">';
            $output .= '<div class=" p-4 text-xs font-medium flex items-center justify-start">';
            
            $icon = !empty($this->attachments[$document]) ? 'check' : 'cross';
            $output .= $this->view->fetchFromTemplate('ui/icons.twig.html', [
                'icon' => $this->mode =='edit' ? $icon : 'file',
                'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2',
            ]);

            $output .= __($document);

            if (!empty($this->attachments[$document])) {
                $output .= '<div class="flex-grow"></div>';
                $output .= Format::tag(__('Uploaded'), 'success -my-2');
            } elseif ($this->mode =='edit' && empty($this->attachments[$document])) {
                $output .= '<div class="flex-grow"></div>';
                $output .= Format::tag(__('Missing'), 'error -my-2');
            } elseif ($this->getRequired()) {
                $output .= '<div class="flex-grow"></div>';
                $output .= Format::tag(__('Required'), 'message -my-2');
            }

            $output .= '</div>';


            $output .= '<div class="document-details border-t  auto-rows-fr py-2" style="grid-template-rows: repeat(2,auto);">';

            $output .= '<div class="px-4 py-2 flex flex-col sm:flex-row justify-between sm:items-center content-center p-0">';
            $row = $this->factory->createRow()->addClass($this->getClass());

            $fieldName = $name.$index.'filePath';
            $input = $row->addFileUpload($fieldName)
                        ->accepts('.jpg,.jpeg,.gif,.png,.pdf,.doc,.docx')
                        ->setMaxUpload(false)
                        ->required($this->getRequired());

            if (!empty($this->attachments[$document])) {
                $input->setAttachment($fieldName.'File', $this->absoluteURL, $this->attachments[$document]);
            }

            $this->validation .= $input->getValidationOutput();

            $output .= $input->getOutput();
            
            $output .= '</div>';
        

            $output .= '</div>';
            $output .= '</div>';
        }

        $output .= "
        <script>
            $('.document-omit').click(function () {
                $(this).parents('.document').find('.document-details').toggle($(this).checked);
            });
            $('.document-omit:checked').each(function () {
                $(this).parents('.document').find('.document-details').hide();
            });
        </script>
        ";

        return $output;
    }
}
