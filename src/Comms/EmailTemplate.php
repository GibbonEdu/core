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

namespace Gibbon\Comms;

use Twig\Environment;
use Faker\Factory as FakerFactory;
use Gibbon\Comms\EmailTemplateLoader;
use Gibbon\Contracts\Services\Session;

/**
 * Handles creating a Twig environment to render Email Templates
 *
 * @version  v21
 * @since    v21
 */
class EmailTemplate
{
    protected $loader;
    protected $templateName;

    protected $defaultData;

    /**
     * Create a new template view.
     *
     * @param $loader
     */
    public function __construct(EmailTemplateLoader $loader, Session $session)
    {
        $this->loader = $loader;
        $this->defaultData = [
            'absolutePath'                   => $session->get('absolutePath'),
            'absoluteURL'                    => $session->get('absoluteURL'),
            'systemName'                     => $session->get('systemName'),
            'organisationName'               => $session->get('organisationName'),
            'organisationNameShort'          => $session->get('organisationNameShort'),
            'organisationAdministratorName'  => $session->get('organisationAdministratorName'),
            'organisationAdministratorEmail' => $session->get('organisationAdministratorEmail'),
        ];
    }

    public function setTemplate($templateName)
    {
        $this->templateName = $templateName;

        return $this;
    }

    public function setTemplateByID($templateID)
    {
        $this->templateName = $this->loader->getNameFromID($templateID);

        return $this;
    }

    public function renderSubject($data)
    {
        $twig = new Environment($this->loader->setSource('templateSubject'));

        return $twig->render($this->templateName, array_merge($this->defaultData, $data));
    }

    public function renderBody($data)
    {
        $twig = new Environment($this->loader->setSource('templateBody'));

        return $twig->render($this->templateName, array_merge($this->defaultData, $data));
    }
    
    public function generateFakeData($variables)
    {
        $faker = FakerFactory::create();

        return array_map(function ($formatter) use (&$faker) {
            return is_array($formatter)
                ? $faker->format($formatter[0], array_slice($formatter, 1) ?? [])
                : $formatter;
        }, $variables);
    }

    public function getDefaultVariables()
    {
        return array_keys($this->defaultData);
    }
    
}
