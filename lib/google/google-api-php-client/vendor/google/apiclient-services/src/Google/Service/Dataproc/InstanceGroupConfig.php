<?php
/*
 * Copyright 2016 Google Inc.
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

class Google_Service_Dataproc_InstanceGroupConfig extends Google_Collection
{
  protected $collection_key = 'instanceNames';
  protected $diskConfigType = 'Google_Service_Dataproc_DiskConfig';
  protected $diskConfigDataType = '';
  public $imageUri;
  public $instanceNames;
  public $isPreemptible;
  public $machineTypeUri;
  protected $managedGroupConfigType = 'Google_Service_Dataproc_ManagedGroupConfig';
  protected $managedGroupConfigDataType = '';
  public $numInstances;

  public function setDiskConfig(Google_Service_Dataproc_DiskConfig $diskConfig)
  {
    $this->diskConfig = $diskConfig;
  }
  public function getDiskConfig()
  {
    return $this->diskConfig;
  }
  public function setImageUri($imageUri)
  {
    $this->imageUri = $imageUri;
  }
  public function getImageUri()
  {
    return $this->imageUri;
  }
  public function setInstanceNames($instanceNames)
  {
    $this->instanceNames = $instanceNames;
  }
  public function getInstanceNames()
  {
    return $this->instanceNames;
  }
  public function setIsPreemptible($isPreemptible)
  {
    $this->isPreemptible = $isPreemptible;
  }
  public function getIsPreemptible()
  {
    return $this->isPreemptible;
  }
  public function setMachineTypeUri($machineTypeUri)
  {
    $this->machineTypeUri = $machineTypeUri;
  }
  public function getMachineTypeUri()
  {
    return $this->machineTypeUri;
  }
  public function setManagedGroupConfig(Google_Service_Dataproc_ManagedGroupConfig $managedGroupConfig)
  {
    $this->managedGroupConfig = $managedGroupConfig;
  }
  public function getManagedGroupConfig()
  {
    return $this->managedGroupConfig;
  }
  public function setNumInstances($numInstances)
  {
    $this->numInstances = $numInstances;
  }
  public function getNumInstances()
  {
    return $this->numInstances;
  }
}
