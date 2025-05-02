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

use Mpdf\Mpdf;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$output = '';

$settingGateway = $container->get(SettingGateway::class);

$publicUnits = $settingGateway->getSettingByScope('Free Learning', 'publicUnits');

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_browse_details.php') == false) {
    //Acess denied
    $output .= "<div class='error'>";
    $output .= __('You do not have access to this action.');
    $output .= '</div>';
} else {
    if ($settingGateway->getSettingByScope('Free Learning', 'certificatesAvailable') != "Y") {
        $output .= "<div class='error'>";
        $output .= __('You do not have access to this action.');
        $output .= '</div>';
    } else {
        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';

        if ($freeLearningUnitID == '') {
            $output .= "<div class='error'>";
            $output .= __('You have not specified one or more required parameters.');
            $output .= '</div>';
        } else {

            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                $sql = "SELECT freeLearningUnitStudent.freeLearningUnitStudentID, freeLearningUnit.name, freeLearningUnit.blurb, officialName, surname, preferredName, firstName, (SELECT sum(length) FROM freeLearningUnitBlock WHERE freeLearningUnitBlock.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) AS length, timestampCompleteApproved
                    FROM freeLearningUnit
                        JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                        JOIN gibbonPerson ON (freeLearningUnitStudent.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                    WHERE freeLearningUnit.freeLearningUnitID=:freeLearningUnitID
                        AND gibbonPersonIDStudent=:gibbonPersonID
                        AND freeLearningUnitStudent.status='Complete - Approved'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $output .= "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                $output .= "<div class='error'>";
                $output .= __('The selected record does not exist, or you do not have access to it.');
                $output .= '</div>';
            } else {
                $row = $result->fetch();

                // Render the certificate based on the HTML template
                $certificateTemplate = $settingGateway->getSettingByScope('Free Learning', 'certificateTemplate');
                $template = $container->get('twig')->createTemplate($certificateTemplate);
                $output = $template->render([
                    'freeLearningUnitStudentID' => $row['freeLearningUnitStudentID'],
                    'officialName'     => $row['officialName'],
                    'preferredName'    => $row['preferredName'],
                    'firstName'        => $row['firstName'],
                    'surname'          => $row['surname'],
                    'unitName'         => $row['name'],
                    'unitBlurb'        => $row['blurb'],
                    'length'           => $row['length'],
                    'dateComplete'     => Format::date($row['timestampCompleteApproved']),
                    'dateCompleteYMD'  => $row['timestampCompleteApproved'],
                    'organisationName' => $session->get('organisationName'),
                    'organisationLogo' => $session->get('organisationLogo'),
                ]);
            }
        }
    }
}

// Create PDF objects
$config = [
    'mode' => 'utf-8',
    'format' => [210, 297],
    'orientation' => $settingGateway->getSettingByScope('Free Learning', 'certificateOrientation'),
    
    'margin_top' => 10,
    'margin_bottom' => 10,
    'margin_left' => 10,
    'margin_right' => 10,

    'setAutoTopMargin' => 'stretch',
    'setAutoBottomMargin' => 'stretch',
    'autoMarginPadding' => 1,

    'shrink_tables_to_fit' => 0,
    'defaultPagebreakType' => 'cloneall',

    'default_font' => 'DejaVuSans',
];

$pdf = new Mpdf($config);

$pdf->SetCreator($session->get('organisationName'));
$pdf->SetAuthor($session->get('organisationName'));
$pdf->SetTitle($session->get('organisationName').' Free Learning');

$pdf->AddPage();

$pdf->WriteHTML($output);

$pdf->Output($session->get('organisationName').' Free Learning.pdf', 'I');
