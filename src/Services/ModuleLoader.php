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

namespace Gibbon\Services;

use Gibbon\Contracts\Services\Session;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;

/**
 * Handles namespace resolution for modules.
 *
 * @version v30
 * @since   v30
 */
class ModuleLoader implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Add a module namespace to the PSR4 autoloader, enabling module-relative 
     * classes to be used with the container.
     *
     * @param string $moduleName
     * @return bool Returns false if the module has not been added to the autoloader.
     */
    public function registerModuleNamespace(string $moduleName) : bool
    {
        $moduleNamespace = preg_replace('/[^a-zA-Z0-9]/', '', $moduleName);

        if (empty($moduleName) || empty($moduleNamespace)) return false;
        
        $session = $this->getContainer()->get(Session::class);
        $autoloader = $this->getContainer()->get('autoloader');

        $path = $session->get('absolutePath').'/modules/'.$moduleName.'/src';

        if (empty($autoloader) || !is_dir($path)) return false;
        
        try {
            $autoloader->addPsr4('Gibbon\\Module\\'.$moduleNamespace.'\\', $path);
            $autoloader->register(true);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
