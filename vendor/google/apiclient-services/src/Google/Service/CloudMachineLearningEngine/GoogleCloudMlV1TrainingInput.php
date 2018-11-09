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

class Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1TrainingInput extends Google_Collection
{
  protected $collection_key = 'packageUris';
  public $args;
  protected $hyperparametersType = 'Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterSpec';
  protected $hyperparametersDataType = '';
  public $jobDir;
  public $masterType;
  public $packageUris;
  public $parameterServerCount;
  public $parameterServerType;
  public $pythonModule;
  public $pythonVersion;
  public $region;
  public $runtimeVersion;
  public $scaleTier;
  public $workerCount;
  public $workerType;

  public function setArgs($args)
  {
    $this->args = $args;
  }
  public function getArgs()
  {
    return $this->args;
  }
  /**
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterSpec
   */
  public function setHyperparameters(Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterSpec $hyperparameters)
  {
    $this->hyperparameters = $hyperparameters;
  }
  /**
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1HyperparameterSpec
   */
  public function getHyperparameters()
  {
    return $this->hyperparameters;
  }
  public function setJobDir($jobDir)
  {
    $this->jobDir = $jobDir;
  }
  public function getJobDir()
  {
    return $this->jobDir;
  }
  public function setMasterType($masterType)
  {
    $this->masterType = $masterType;
  }
  public function getMasterType()
  {
    return $this->masterType;
  }
  public function setPackageUris($packageUris)
  {
    $this->packageUris = $packageUris;
  }
  public function getPackageUris()
  {
    return $this->packageUris;
  }
  public function setParameterServerCount($parameterServerCount)
  {
    $this->parameterServerCount = $parameterServerCount;
  }
  public function getParameterServerCount()
  {
    return $this->parameterServerCount;
  }
  public function setParameterServerType($parameterServerType)
  {
    $this->parameterServerType = $parameterServerType;
  }
  public function getParameterServerType()
  {
    return $this->parameterServerType;
  }
  public function setPythonModule($pythonModule)
  {
    $this->pythonModule = $pythonModule;
  }
  public function getPythonModule()
  {
    return $this->pythonModule;
  }
  public function setPythonVersion($pythonVersion)
  {
    $this->pythonVersion = $pythonVersion;
  }
  public function getPythonVersion()
  {
    return $this->pythonVersion;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setRuntimeVersion($runtimeVersion)
  {
    $this->runtimeVersion = $runtimeVersion;
  }
  public function getRuntimeVersion()
  {
    return $this->runtimeVersion;
  }
  public function setScaleTier($scaleTier)
  {
    $this->scaleTier = $scaleTier;
  }
  public function getScaleTier()
  {
    return $this->scaleTier;
  }
  public function setWorkerCount($workerCount)
  {
    $this->workerCount = $workerCount;
  }
  public function getWorkerCount()
  {
    return $this->workerCount;
  }
  public function setWorkerType($workerType)
  {
    $this->workerType = $workerType;
  }
  public function getWorkerType()
  {
    return $this->workerType;
  }
}
