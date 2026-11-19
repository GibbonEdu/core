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

namespace Gibbon\Module\Reports;

use Gibbon\View\Sandbox;
use Gibbon\Services\Format;
use Gibbon\Contracts\Services\Session;
use Twig\Loader\FilesystemLoader;
use Twig\Sandbox\Sandbox as TwigSandbox;
use Twig\Sandbox\SecurityPolicy;
use Twig\Environment;

/**
 * A sandboxed Twig environment for rendering user-provided Twig templates, 
 * specifically for Report templates.
 * 
 *
 * @version  v30.0.03
 * @since    v30.0.03
 */
class ReportSandbox extends Sandbox
{
    /**
     * @var \Twig\Environment
     */
    protected $env;

    /**
     * Create a new sandboxed Twig environment.
     */
    public function __construct(Session $session)
    {
        $loader = new FilesystemLoader($session->get('absolutePath').'/modules/Reports/templates');

        $this->env = new Environment($loader, ['cache' => $session->get('absolutePath').'/uploads/cache/reports']);

        $this->env->addFunction(new \Twig\TwigFunction('__', function ($string, $domain = null) {
            return __($string, $domain);
        }));

        $this->env->addFunction(new \Twig\TwigFunction('__m', function ($string, $params = []) {
            return __m($string, $params);
        }));

        $this->env->addFunction(new \Twig\TwigFunction('__n', function ($singular, $plural, $n, $params = [], $options = []) {
            return __n($singular, $plural, $n, $params, $options);
        }));

        $this->env->addFunction(new \Twig\TwigFunction('formatUsing', function ($method, ...$args) {
            return Format::$method(...$args);
        }, ['is_safe' => ['html']]));

        $this->sandbox = new TwigSandbox($this->env, $this->getSecurityPolicy());
    }

    /**
     * Get the Twig environment FilesystemLoader, to append custom file paths.
     *
     * @return FilesystemLoader
     */
    public function getLoader() : FilesystemLoader
    {
        return $this->env->getLoader();
    }

    /**
     * Defines the sandbox allowlist for the security policy. Reports have an 
     * extended allowlist compared to the default sandbox, including additional 
     * filters and functions.
     *
     * @return SecurityPolicy
     */
    protected function getSecurityPolicy() : SecurityPolicy
    {
        $policy = new SecurityPolicy(
            allowedTags: ['include', 'if', 'for', 'set', 'with'],
            allowedFilters: ['abs','capitalize','country_name','currency_name','currency_symbol','date','date_modify','default','escape','first','format','format_currency','format_date','format_datetime','format_number','format_time','join','keys','language_name','last','length','locale_name','lower','merge','nl2br','number_format','raw','replace','round','slug','spaceless','split','title','trim','upper','url_encode','reverse','filter','sort'],
            allowedFunctions: ['include','range','date','random','min','max','cycle','formatUsing','__','__m','__n'],
        );
        $policy->setStrict(true);

        return $policy;
    }
}
