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

use Twig\Source;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Gibbon\Domain\System\EmailTemplateGateway;

class EmailTemplateLoader implements LoaderInterface
{
    protected $emailTemplateGateway;
    protected $source = 'templateBody';

    public function __construct(EmailTemplateGateway $emailTemplateGateway)
    {
        $this->emailTemplateGateway = $emailTemplateGateway;
    }

    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    public function getSourceContext($name): Source
    {
        if (false === $template = $this->getTemplate($name)) {
            throw new LoaderError(sprintf('Template "%s" does not exist.', $name));
        }

        return new Source($template[$this->source] ?? '', $name);
    }

    public function exists($name)
    {
        return (bool)$this->getTemplate($name);
    }

    public function getCacheKey($name): string
    {
        return $name.$this->source;
    }

    public function isFresh($name, $time): bool
    {
        if (false === $template = $this->getTemplate($name)) {
            return false;
        }

        return strtotime($template['timestamp']) <= $time;
    }

    public function getNameFromID($id)
    {
        return $this->emailTemplateGateway->selectBy(['gibbonEmailTemplateID' => $id], ['templateName'])->fetch(\PDO::FETCH_COLUMN, 0);
    }

    /**
     * @param $name
     * @return array|null
     */
    protected function getTemplate($name)
    {
        return $this->emailTemplateGateway->selectBy(['templateName' => $name])->fetch();  
    }
}
