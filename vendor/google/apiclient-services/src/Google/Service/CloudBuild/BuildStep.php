<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_CloudBuild_BuildStep extends Google_Collection
{
  protected $collection_key = 'waitFor';
  public $args;
  public $dir;
  public $entrypoint;
  public $env;
  public $id;
  public $name;
  protected $pullTimingType = 'Google_Service_CloudBuild_TimeSpan';
  protected $pullTimingDataType = '';
  public $secretEnv;
  public $status;
  public $timeout;
  protected $timingType = 'Google_Service_CloudBuild_TimeSpan';
  protected $timingDataType = '';
  protected $volumesType = 'Google_Service_CloudBuild_Volume';
  protected $volumesDataType = 'array';
  public $waitFor;

  public function setArgs($args)
  {
    $this->args = $args;
  }
  public function getArgs()
  {
    return $this->args;
  }
  public function setDir($dir)
  {
    $this->dir = $dir;
  }
  public function getDir()
  {
    return $this->dir;
  }
  public function setEntrypoint($entrypoint)
  {
    $this->entrypoint = $entrypoint;
  }
  public function getEntrypoint()
  {
    return $this->entrypoint;
  }
  public function setEnv($env)
  {
    $this->env = $env;
  }
  public function getEnv()
  {
    return $this->env;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param Google_Service_CloudBuild_TimeSpan
   */
  public function setPullTiming(Google_Service_CloudBuild_TimeSpan $pullTiming)
  {
    $this->pullTiming = $pullTiming;
  }
  /**
   * @return Google_Service_CloudBuild_TimeSpan
   */
  public function getPullTiming()
  {
    return $this->pullTiming;
  }
  public function setSecretEnv($secretEnv)
  {
    $this->secretEnv = $secretEnv;
  }
  public function getSecretEnv()
  {
    return $this->secretEnv;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setTimeout($timeout)
  {
    $this->timeout = $timeout;
  }
  public function getTimeout()
  {
    return $this->timeout;
  }
  /**
   * @param Google_Service_CloudBuild_TimeSpan
   */
  public function setTiming(Google_Service_CloudBuild_TimeSpan $timing)
  {
    $this->timing = $timing;
  }
  /**
   * @return Google_Service_CloudBuild_TimeSpan
   */
  public function getTiming()
  {
    return $this->timing;
  }
  /**
   * @param Google_Service_CloudBuild_Volume
   */
  public function setVolumes($volumes)
  {
    $this->volumes = $volumes;
  }
  /**
   * @return Google_Service_CloudBuild_Volume
   */
  public function getVolumes()
  {
    return $this->volumes;
  }
  public function setWaitFor($waitFor)
  {
    $this->waitFor = $waitFor;
  }
  public function getWaitFor()
  {
    return $this->waitFor;
  }
}
