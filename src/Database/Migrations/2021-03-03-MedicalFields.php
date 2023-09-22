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

use Gibbon\Contracts\Database\Connection;
use Gibbon\Database\Migrations\Migration;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;

/**
 * Medical Fields migration - removes Blood Type and Tetanus fields and migrates them into custom fields.
 */
class MedicalFields extends Migration
{
    protected $db;
    protected $customFieldGateway;
    protected $medicalGateway;
    protected $medicalUpdateGateway;

    public function __construct(Connection $db, CustomFieldGateway $customFieldGateway, MedicalGateway $medicalGateway, MedicalUpdateGateway $medicalUpdateGateway)
    {
        $this->db = $db;
        $this->customFieldGateway = $customFieldGateway;
        $this->medicalGateway = $medicalGateway;
        $this->medicalUpdateGateway = $medicalUpdateGateway;
    }   

    public function migrate()
    {
        $partialFail = false;

        // Prevent running this migration if the field has already been removed
        $fieldPresent = $this->db->select("SHOW COLUMNS FROM `gibbonPersonMedical` LIKE 'bloodType'")->fetchAll();
        if (empty($fieldPresent)) return true;

        // Create the Blood Type and Tetanus custom fields
        $data = [
            'context'                  => 'Medical Form',
            'name'                     => 'Blood Type',
            'active'                   => 'Y',
            'description'              => '',
            'type'                     => 'select',
            'options'                  => 'O+,A+,B+,AB+,O-,A-,B-,AB-',
            'required'                 => 'N',
            'heading'                  => '',
            'activeDataUpdater'        => '1',
        ];
        $bloodTypeFieldID = $this->customFieldGateway->insertAndUpdate($data, $data);
        $bloodTypeFieldID = str_pad($bloodTypeFieldID, 4, '0', STR_PAD_LEFT);

        $data = [
            'context'                  => 'Medical Form',
            'name'                     => 'Tetanus Within Last 10 Years?',
            'active'                   => 'Y',
            'description'              => '',
            'type'                     => 'yesno',
            'options'                  => '',
            'required'                 => 'N',
            'heading'                  => '',
            'activeDataUpdater'        => '1',
        ];
        $tetanusFieldID = $this->customFieldGateway->insert($data, $data);
        $tetanusFieldID = str_pad($tetanusFieldID, 4, '0', STR_PAD_LEFT);

        // Loop all medical forms and move fields into custom field data, merge existing
        $medicalForms = $this->medicalGateway->selectBy([])->fetchAll();
        foreach ($medicalForms as $form) {
            $fields = !empty($form['fields']) ? json_decode($form['fields'], true) : [];

            $fields[$bloodTypeFieldID] = $form['bloodType'];
            $fields[$tetanusFieldID] = $form['tetanusWithin10Years'];
            
            $updated = $this->medicalGateway->update($form['gibbonPersonMedicalID'], ['fields' => json_encode($fields)]);
            $partialFail &= !$updated;
        }

        // Loop all medical updates and move fields into custom field data, merge existing
        $medicalFormUpdates = $this->medicalUpdateGateway->selectBy([])->fetchAll();
        foreach ($medicalFormUpdates as $form) {
            $fields = !empty($form['fields']) ? json_decode($form['fields'], true) : [];

            $fields[$bloodTypeFieldID] = $form['bloodType'];
            $fields[$tetanusFieldID] = $form['tetanusWithin10Years'];
            
            $updated = $this->medicalUpdateGateway->update($form['gibbonPersonMedicalUpdateID'], ['fields' => json_encode($fields)]);
            $partialFail &= !$updated;
        }

        // Remove fields from gibbonPersonMedical, gibbonPersonMedicalUpdate tables
        $sql = "ALTER TABLE `gibbonPersonMedical` DROP `bloodType`, DROP `tetanusWithin10Years`;";
        $this->db->statement($sql);

        $sql = "ALTER TABLE `gibbonPersonMedicalUpdate` DROP `bloodType`, DROP `tetanusWithin10Years`;";
        $this->db->statement($sql);

        return !$partialFail;
    }
}
