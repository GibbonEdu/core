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

namespace Gibbon\View;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Twig\Sandbox\Sandbox as TwigSandbox;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityPolicy;

/**
 * A sandboxed Twig environment for rendering user-provided Twig template strings.
 * 
 * The sandbox does not have access to global variables or functions, and has a 
 * limited set of filters and tags allowed.
 * 
 * In particular, filters that work with arrays or use anonymous functions are
 * not allowed. Functions that include other files are not allowed.
 *
 * @version  v30.0.03
 * @since    v27
 */
class Sandbox 
{
    /**
     * @var \Twig\Sandbox\Sandbox
     */
    protected $sandbox;

    /**
     * Create a new sandboxed Twig environment.
     */
    public function __construct()
    {
        $loader = new ArrayLoader([
            'sandbox.html.twig' => '',
        ]);

        $env = new Environment($loader, ['cache' => false]);

        $this->sandbox = new TwigSandbox($env, $this->getSecurityPolicy());
    }

    /**
     * Set the loader for the sandbox environment.
     *
     * @param LoaderInterface $loader
     */
    public function setLoader(LoaderInterface $loader): void
    {
        $env = new Environment($loader, ['cache' => false]);

        $this->sandbox = new TwigSandbox($env, $this->getSecurityPolicy());
    }

    /**
     * Render a template by name with the given data and return the result as a string.
     *
     * @param string $templateName
     * @param array $data
     * @return string
     */
    public function render(string $templateName, array $data = []) : string
    {
        try {
            return $this->sandbox->render($templateName, $data);

        } catch (SecurityNotAllowedFilterError $e) {
            return $e->getMessage();
        }
    }

    /**
     * Render raw template code and return the result as a string.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function renderContent(string $templateContent, array $data = []) : string
    {
        try {
            return $this->sandbox->createTemplate($templateContent)->render($data);

        } catch (SecurityNotAllowedFilterError $e) {
            return $e->getMessage();
        }
    }

    /**
     * Defines the sandbox allowlist for the security policy.
     *
     * @return SecurityPolicy
     */
    protected function getSecurityPolicy() : SecurityPolicy
    {
        $policy = new SecurityPolicy(
            allowedTags: ['if', 'for', 'set', 'with'],
            allowedFilters: ['abs','capitalize','country_name','currency_name','currency_symbol','date','date_modify','default','escape','first','format','format_currency','format_date','format_datetime','format_number','format_time','join','keys','language_name','last','length','locale_name','lower','merge','nl2br','number_format','replace','round','slug','spaceless','split','title','trim','upper','url_encode'],
            allowedFunctions: ['range','date','random','min','max','cycle'],
        );
        $policy->setStrict(true);

        return $policy;
    }
}
