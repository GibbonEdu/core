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

/**
 * CodeEditor
 *
 * @version v20
 * @since   v20
 */
class PersonalDocuments extends Input
{
    protected $view;
    protected $factory;
    protected $documents;
    protected $validation;

    protected $absoluteURL;
    protected $nationalityList;
    protected $residencyStatus;

    public function __construct(FormFactory &$factory, $name, $documents, View $view, SettingGateway $settingGateway)
    {
        $this->view = $view;
        $this->factory = $factory;
        $this->documents = $documents;

        $this->absoluteURL = $settingGateway->getSettingByScope('System', 'absoluteURL');
        $this->nationalityList = $settingGateway->getSettingByScope('User Admin', 'nationality');
        $this->residencyStatus = $settingGateway->getSettingByScope('User Admin', 'residencyStatus');

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

    /**
     * Gets the HTML output for this form element.
     * @return  string
     */
    protected function getElement()
    {
        $output = '';

        $name = $this->getName();

        foreach ($this->documents as $document) {
            $fields = json_decode($document['fields']);

            $output .= '<input type="hidden" name="'.$name.'['.$document['gibbonPersonalDocumentTypeID'].'][gibbonPersonalDocumentID]" value="'.($document['gibbonPersonalDocumentID'] ?? '').'">';

            $output .= '<div class="document rounded-sm bg-white border font-sans mt-4">';
            $output .= '<div class=" p-4 text-xs font-medium flex items-center justify-between">';
            
            $output .= $this->view->fetchFromTemplate('ui/icons.twig.html', [
                'icon' => strtolower($document['document']),
                'iconClass' => 'w-6 h-6 fill-current mr-3 -my-2',
            ]);

            $output .= __($document['name']);

            if ($document['required'] == 'Y') {
                $output .= '<span class="ml-4 -my-2 tag message">'.__('Required').'</span>';
                $output .= '<div class="flex-grow"></div>';
            } else {
                $fieldsUsed = count(array_filter(array_intersect_key($document, array_flip($fields))));
                $output .= '<div class="flex-grow"></div>';
                $output .= '<span class="font-normal text-xxs">'.__('N/A');
                $output .= '<input class="document-omit ml-2" type="checkbox" value="Y" name="'.$name.'['.$document['gibbonPersonalDocumentTypeID'].'][omit]" '.(!empty($document['gibbonPersonalDocumentID']) && $fieldsUsed == 0? 'checked' : '').'>';
                $output .= '</span>';
            }

            $output .= '</div>';

            $output .= !empty($document['description']) ? '<p class="m-0 p-0 -mt-2 ml-1 pl-12 pb-2 font-normal text-xxs text-gray-600">'.$document['description'].'</p>' : '';

            $output .= '<div class="document-details border-t sm:grid grid-cols-2 grid-flow-col auto-rows-fr py-2" style="grid-template-rows: repeat('.(ceil(count($fields)/2)).',auto);">';

            foreach ($fields as $index => $field) {
                $output .= '<div class="px-4 py-2 flex flex-col sm:flex-row justify-between sm:items-center content-center p-0">';
                $row = $this->factory->createRow()->addClass($this->getClass());

                $fieldID = $name.$document['gibbonPersonalDocumentTypeID'].$field;
                $fieldName = $name.'['.$document['gibbonPersonalDocumentTypeID'].']['.$field.']';
                $label = $input = null;
                
                switch ($field) {
                    case 'documentName':
                        $label = $row->addLabel($field, __('Name on {document}', ['document' => __($document['document'])]));
                        $input = $row->addTextField($field)->maxLength(120)->required($document['required'] == 'Y');
                        break;
                    case 'documentNumber':
                        $label = $row->addLabel($field, __('{document} Number', ['document' => __($document['document'])]));
                        $input = $row->addTextField($field)->maxLength(120)->required($document['required'] == 'Y');
                        break;
                    case 'documentType':
                        $label = $row->addLabel($field, __('Residency/Visa Type'));
                        $input = !empty($this->residencyStatus)
                            ? $row->addSelect($field)->fromString($this->residencyStatus)->placeholder()
                            : $row->addTextField($field)->maxLength(60)->required($document['required'] == 'Y');
                        break;
                    case 'country':
                        $label = $row->addLabel($field, __('Issuing Country'));
                        $input = !empty($this->nationalityList) && $document['document'] != 'Document'
                            ? $row->addSelect($field)->fromString($this->nationalityList)->placeholder()
                            : $row->addSelectCountry($field)->required($document['required'] == 'Y');
                        break;
                    case 'dateIssue':
                        $label = $row->addLabel($field, __('Issue Date'));
                        $input = $row->addDate($field)->required($document['required'] == 'Y' && $index == 0);
                        break;
                    case 'dateExpiry':
                        $label = $row->addLabel($field, __('Expiry Date'));
                        $input = $row->addDate($field)->required($document['required'] == 'Y' && $index == 0);
                        break;
                    case 'filePath':
                        $fieldName = $fieldID;
                        $label = $row->addLabel($field, __('Scanned Copy'));
                        $input = $row->addFileUpload($field)
                                     ->accepts('.jpg,.jpeg,.gif,.png,.pdf,.doc,.docx')
                                     ->setMaxUpload(false)
                                     ->required($document['required'] == 'Y' && $index == 0);
                        if (!empty($document['filePath'])) {
                            $input->setAttachment($name.'['.$document['gibbonPersonalDocumentTypeID'].']['.$field.']', $this->absoluteURL, $document['filePath']);
                        }
                        break;
                }

                if ($label && $input) {
                    $input->loadFrom($document)->setName($fieldName)->setID($fieldID);
                    
                    $output .= $label->setClass('inline-block w-32 font-medium text-xs text-gray-700')->getOutput();
                    $output .= $input->getOutput();
                    $this->validation .= $input->getValidationOutput();
                }
                $output .= '</div>';
            }

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
