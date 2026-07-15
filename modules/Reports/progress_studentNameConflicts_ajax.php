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

use Gibbon\Forms\FormFactory;
use Gibbon\Http\Url;
use Gibbon\Module\Reports\Domain\ReportingProgressGateway;

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/progress_studentNameConflicts.php') == false) {
    exit;
} else {
    // Proceed!
    $gibbonReportingProgressID = $_GET['gibbonReportingProgressID'] ?? '';
    $checked = !empty($_GET['checked']) && $_GET['checked'] == 'Y' ? 'Y' : 'N';

    if (empty($gibbonReportingProgressID)) {
        exit;
    }

    $updated = $container->get(ReportingProgressGateway::class)->update($gibbonReportingProgressID, ['checked' => $checked]);
    $url = Url::fromModuleRoute('Reports', 'progress_studentNameConflicts_ajax.php')
        ->withQueryParams(['gibbonReportingProgressID' => $gibbonReportingProgressID, 'checked' => $checked == 'Y' ? 'N' : 'Y'])
        ->directLink();

    echo $container->get(FormFactory::class)
        ->createButton('')
        ->setIcon('solid', $checked == 'Y' ? 'check' : 'question-mark', $checked == 'Y' ? 'size-5 text-green-600' : 'size-5')
        ->setAttribute('hx-get', $url)
        ->setAttribute('hx-target', 'this')
        ->setAttribute('hx-push-url', 'false')
        ->setAttribute('hx-swap', 'outerHTML show:none swap:0s')
        ->getOutput();

}
