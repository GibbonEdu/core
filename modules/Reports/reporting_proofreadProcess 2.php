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

use Gibbon\Module\Reports\Domain\ReportingProofGateway;
use Gibbon\Module\Reports\Domain\ReportingValueGateway;
use Gibbon\Data\Validator;

$_POST['address'] = '/modules/Reports/reporting_proofread.php';

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$mode = $_POST['mode'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$page = $_POST['page'] ?? '';
$filter = $_POST['filter'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_proofread.php&mode='.$mode.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&page='.$page.'&filter='.$filter;

if (!empty($_POST['override'])) {
    $URL .= '&override='.($_POST['override'] ?? '');
}

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_proofread.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $updated = false;

    $comments = $_POST['comment'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $reasons = $_POST['reason'] ?? [];
    $proofs = $_POST['proof'] ?? [];

    $reportingProofGateway = $container->get(ReportingProofGateway::class);
    $reportingValueGateway = $container->get(ReportingValueGateway::class);

    foreach ($statuses as $gibbonReportingValueID => $status) {
        if ($status == 'Accepted' || $status == 'Declined' || $status == 'Revised') {
            // Action and update the proof record
            $gibbonReportingProofID = $proofs[$gibbonReportingValueID];
            if (!empty($gibbonReportingProofID)) {
                $data = [
                    'status'                 => $status == 'Revised' ? 'Accepted' : $status,
                    'gibbonPersonIDActioned' => $session->get('gibbonPersonID'),
                    'timestampActioned'      => date('Y-m-d H:i:s'),
                ];
                $updated = $reportingProofGateway->update($gibbonReportingProofID, $data);
            }

            // Update the comment text of the report using the proof text
            if ($status == 'Accepted' && !empty($gibbonReportingProofID)) {
                $proof = $reportingProofGateway->getByID($gibbonReportingProofID);
                $updated &= $reportingValueGateway->update($gibbonReportingValueID, [
                    'comment' => $proof['comment'],
                ]);
            }

            // Update the comment text of the report using the revised text
            if ($status == 'Revised' && !empty($comments[$gibbonReportingValueID])) {
                $updated &= $reportingValueGateway->update($gibbonReportingValueID, [
                    'comment' => $comments[$gibbonReportingValueID],
                ]);
            }
        } elseif ($status == 'Done' || $status == 'Edited') {
            // Submit the proof read status and comment
            $data = [
                'gibbonReportingValueID' => $gibbonReportingValueID,
                'status'                 => $status,
                'comment'                => $status == 'Edited' ? ($comments[$gibbonReportingValueID] ?? '') : '',
                'reason'                 => $reasons[$gibbonReportingValueID] ?? '',
                'gibbonPersonIDProofed'  => $session->get('gibbonPersonID'),
                'timestampProofed'       => date('Y-m-d H:i:s'),
            ];

            $updated = $reportingProofGateway->insertAndUpdate($data, [
                'status' => $data['status'],
                'comment' => $data['comment'],
                'reason' => $data['reason'],
                'gibbonPersonIDProofed' => $data['gibbonPersonIDProofed'],
                'timestampProofed' => $data['timestampProofed'],
            ]);
        }
        
        $partialFail &= !$updated;
    }

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
