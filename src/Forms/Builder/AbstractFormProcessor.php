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

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use League\Container\Exception\NotFoundException;
use Gibbon\Forms\Builder\FormBuilderInterface;
use Gibbon\Forms\Builder\Storage\FormDataInterface;
use Gibbon\Forms\Builder\Exception\MissingFieldException;
use Gibbon\Forms\Builder\Exception\MissingValueException;
use Gibbon\Forms\Builder\Process\ViewableProcess;

abstract class AbstractFormProcessor implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private $mode = 'run';

    /**
     * @var FormBuilderInterface
     */
    private $builder;

    /**
     * @var FormDataInterface
     */
    private $data;

    /**
     * @var string[]
     */
    private $processes = [];

    /**
     * @var string[]
     */
    private $errors = [];

    abstract public function submitProcess();

    abstract public function editProcess();

    abstract public function acceptProcess();

    public function submitForm(FormBuilderInterface $builder, FormDataInterface $data)
    {
        $this->mode = 'run';
        $this->builder = $builder;
        $this->data = $data;
        
        $this->submitProcess();
    }

    public function editForm(FormBuilderInterface $builder, FormDataInterface $data)
    {
        $this->mode = 'run';
        $this->builder = $builder;
        $this->data = $data;

        $this->editProcess();
    }

    public function acceptForm(FormBuilderInterface $builder, FormDataInterface $data)
    {
        $this->mode = 'run';
        $this->builder = $builder;
        $this->data = $data;

        $this->acceptProcess();
    }

    public function verifyForm(FormBuilderInterface $builder)
    {
        $this->mode = 'verify';
        $this->builder = $builder;

        $this->submitProcess();
        $this->editProcess();
        $this->acceptProcess();

        return $this->errors;
    }

    public function getProcesses()
    {
        $this->mode = 'boot';

        $this->submitProcess();
        $this->editProcess();
        $this->acceptProcess();

        return $this->processes;
    }

    public function getViewableProcesses()
    {
        return array_filter($this->getProcesses(), function ($process) {
            return $process instanceof ViewableProcess;
        });
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function run(string $processClass)
    {
        try {
            $process = $this->getProcess($processClass);

            if ($this->mode == 'verify') {
                $process->verify($this->builder);
                $process->setVerified();
            } elseif ($this->mode == 'run') {
                $process->verify($this->builder, $this->data);
                $process->process($this->builder, $this->data);
                $process->setProcessed();
            }

        } catch (NotFoundException $e) {
            $this->errors[] = __('Invalid process class: {className}', ['className' => $processClass]);
        } catch (MissingFieldException $e) {
            $this->errors[] = __('Missing required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        } catch (MissingValueException $e) {
            $this->errors[] = __('Missing value for required field: {fieldName}', ['fieldName' => $e->getMessage()]);
        }
    }

    private function getProcess(string $processClass)
    {
        if (isset($this->processes[$processClass])) {
            return $this->processes[$processClass];
        }

        $this->processes[$processClass] = $this->getContainer()->get($processClass);

        return $this->processes[$processClass];
    }
}
