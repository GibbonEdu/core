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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/cacheManager.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Cache Manager'));

    $settingGateway = $container->get(SettingGateway::class);
    $setting = $settingGateway->getSettingByScope('System', 'cachePath', true);

    // CACHE CHECK
    $cachePath = $session->get('absolutePath') . $setting['value'];
    $fileCount = $fileWriteable = $templatesSize = $reportsSize = 0;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cachePath), RecursiveIteratorIterator::SELF_FIRST);

    while($iterator->valid()) {
        $subPath = $cachePath.'/'.$iterator->getSubPathName();

        $fileCount++;
        $fileWriteable += is_writeable($subPath);
        if (stripos($iterator->getSubPath(), 'reports/') !== false) {
            $reportsSize += intval(filesize($subPath));
        } else {
            $templatesSize += intval(filesize($subPath));
        }
        
        $iterator->next();
    }

    // mPDF Cache Check
    $mPDFCachePath = $session->get('absolutePath').'/vendor/mpdf/mpdf/tmp';

    if (!is_dir($cachePath) || !is_writeable($cachePath)) {
        echo Format::alert(__('Your cache directory is missing or is not system writeable. Check the file permissions in your cache directory and resolve these errors manually.'), 'error');
    } elseif ($fileCount != $fileWriteable) {
        echo Format::alert(__('{count} files or folders in the cache directory are not system writeable. This will cause errors if the cache system cannot update or delete these files. Check the file permissions in your cache directory and resolve these errors manually.', ['count' => $fileCount - $fileWriteable]), 'error');
    } else if (!is_writeable($mPDFCachePath)) {
        echo Format::alert(__('The path {path} is missing or is not system writeable. Check the file permissions on your server and resolve these errors manually.', ['path' => $mPDFCachePath]), 'error');
    } else {
        echo Format::alert(__('Caching is running smoothly. All files and folders in your cache directory are system writeable.'), 'success');
    }

    // FORM
    $form = Form::create('cacheSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cacheManager_settingsProcess.php');

    $form->addRow()->addHeading('Settings', __('Settings'));

    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->required()->setValue($setting['value']);

    $row = $form->addRow()->addSubmit();

    echo $form->getOutput();

    // CLEAR CACHE
    $form = Form::create('clearCache', $session->get('absoluteURL').'/modules/'.$session->get('module').'/cacheManager_clearCacheProcess.php');
    $form->addClass('mt-10');

    $form->addRow()->addHeading('System Data', __('System Data'));

    $row = $form->addRow();
        $row->addLabel('templateCache', __('Template Cache'));
        $row->addContent(Format::tag(Format::filesize($templatesSize), 'dull'));
        $row->addCheckbox('templateCache')->setValue('Y')->checked('N');

    $row = $form->addRow();
        $row->addLabel('reportsCache', __('Reports Cache'));
        $row->addContent(Format::tag(Format::filesize($reportsSize), 'dull'));
        $row->addCheckbox('reportsCache')->setValue('Y')->checked('N');

    $row = $form->addRow();
        $row->addLabel('frontEndCache', __('Front End Cache'));
        $row->addContent(Format::tag('.css .js', 'dull'));
        $row->addCheckbox('frontEndCache')->setValue('Y')->checked('N');

    $row = $form->addRow()->addSubmit(__('Clear Cache'));

    echo $form->getOutput();
}
