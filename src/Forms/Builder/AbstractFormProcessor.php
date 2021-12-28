<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

namespace Gibbon\Forms\Builder;

use Gibbon\Forms\Builder\AbstractFormProcess;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;

abstract class AbstractFormProcessor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var FormData
     */
    protected $data;

    /**
     * @var array[]
     */
    protected $processes = [];

    /**
     * @var string[]
     */
    protected $errors = [];

    abstract public function submitProcess();

    public function editProcess() {}

    public function acceptProcess() {}

    public function submitForm(FormData $data)
    {
        $this->data = $data;
        $this->submitProcess();
    }

    public function editForm(FormData $data)
    {
        $this->data = $data;
        $this->editProcess();
    }

    public function acceptForm(FormData $data)
    {
        $this->data = $data;
        $this->acceptProcess();
    }

    public function run(string $processClass)
    {
        try {
            $process = $this->getContainer()->get($processClass);
            $process->process($this->data);

        } catch (NotFoundException $e) {
            $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
        } catch (MissingFieldException $e) {
            $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        }
    }

    public function validate(FormData $data)
    {
        foreach ($this->processes as $processName => $processDetails) {
            try {
                $processClass = $processDetails['process'] ?? '';

                $process = $this->getContainer()->get($processClass);
                $process->validate($data);

                $this->processes[$processName]['valid'] = true;

            } catch (NotFoundException $e) {
                $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
            } catch (MissingFieldException $e) {
                $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
            }
        }

        return $this->errors;
    }

    public function getProcesses()
    {
        return $this->processes;
    }
}
