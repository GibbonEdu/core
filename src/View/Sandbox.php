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
use Twig\Loader\ChainLoader;
use Twig\Loader\ArrayLoader;
use Twig\Extension\SandboxExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityNotAllowedFilterError;

/**
 * A sandboxed Twig environment for rendering user-provided Twig template strings.
 * 
 * The sandbox does not have access to global variables or functions, and has a 
 * limited set of filters and tags allowed.
 * 
 * Warning: should not be used repeatedly in loops, as the render methods needs 
 * to create new objects for each method call, based on Twig limitations.
 * 
 * In particular, filters that work with arrays or use anonymous functions are
 * not allowed. Functions that include other files are not allowed.
 *
 * @version  v27
 * @since    v27
 */
class Sandbox 
{
    /**
     * Render the view with the given template and return the result as a string.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    public function render(string $templateContent, array $data = []) : string
    {
        try {
            $contentLoader = new ArrayLoader([
                'content.html.twig' => $templateContent,
            ]);
            $sandboxLoader = new ArrayLoader([
                'sandbox.html.twig' => '{% sandbox %}{% include "content.html.twig" %}{% endsandbox %}',
            ]);
            
            $loader = new ChainLoader([$contentLoader, $sandboxLoader]);
            $twig = new Environment($loader, ['cache' => false]);
            $twig->addExtension($this->getSandboxExtension());

            return $twig->render('sandbox.html.twig', $data);
        } catch (SecurityNotAllowedFilterError $e) {
            return $e->getMessage();
        }
    }

    /**
     * Sets up the SandboxExtension for inclusion in the Twig Environment.
     *
     * @return ExtensionInterface
     */
    protected function getSandboxExtension() : ExtensionInterface
    {
        return new SandboxExtension($this->getSecurityPolicy());
    }

    /**
     * Defines the sandbox allowlist for the security policy.
     *
     * @return SecurityPolicy
     */
    protected function getSecurityPolicy() : SecurityPolicy
    {
        // Define a sandbox security policy
        $tags = ['if', 'for', 'set', 'with'];
        $filters = ['abs','capitalize','country_name','currency_name','currency_symbol','date','date_modify','default','escape','first','format','format_currency','format_date','format_datetime','format_number','format_time','join','keys','language_name','last','length','locale_name','lower','merge','nl2br','number_format','replace','round','slug','spaceless','split','title','trim','upper','url_encode'];
        $methods = [];
        $properties = [];
        $functions = ['range','date','random','min','max','cycle'];

        return new SecurityPolicy($tags, $filters, $methods, $properties, $functions);
    }
}
