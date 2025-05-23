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

use GuzzleHttp\Client;
use Gibbon\Forms\Form;
use Gibbon\View\View;
use Gibbon\Services\Format;

include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/services_manage.php') == false) {
    return;
} else {
    // Proceed!
    $apiEndpoint = 'https://gibbonedu.org/gibboneducom/servicesAPI.php';
    $params = [
        'gibboneduComOrganisationName'  => $_POST['gibboneduComOrganisationName'] ?? '',
        'gibboneduComOrganisationKey'  => $_POST['gibboneduComOrganisationKey'] ?? '',
    ];

    if (empty($params['gibboneduComOrganisationName']) || empty($params['gibboneduComOrganisationKey'])) {
        return;
    }

    // Make a request using a Guzzle HTTP get request
    $client = new Client();
    $response = $client->request('GET', $apiEndpoint, [
        'headers' => ['Referer' => $session->get('absoluteURL').'/index.php'],
        'query' => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
        'exceptions' => false,
    ]);

    // Fetch the result as json
    $result = json_decode($response->getBody(), true);

    if ($response->getStatusCode() != 200) {
        echo Format::alert(__('Failed to connect to gibbonedu.com server.').' '.__('Please contact support@gibbonedu.com if the problem persists.'));
        return;
    }

    if ($result['access'] == false) {
        echo Format::alert(__('Failed to authenticate service key. Check that your organization name and key are entered correctly.').' '.__('Please contact support@gibbonedu.com if the problem persists.'));
        return;
    }

    // Display the gibbonedu.com services info.
    echo $container->get(View::class)->fetchFromTemplate('service.twig.html', $result);
}
