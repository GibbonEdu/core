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

class Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1Version extends Google_Model
{
  protected $automaticScalingType = 'Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1AutomaticScaling';
  protected $automaticScalingDataType = '';
  public $createTime;
  public $deploymentUri;
  public $description;
  public $errorMessage;
  public $isDefault;
  public $lastUseTime;
  protected $manualScalingType = 'Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1ManualScaling';
  protected $manualScalingDataType = '';
  public $name;
  public $runtimeVersion;
  public $state;

  /**
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1AutomaticScaling
   */
  public function setAutomaticScaling(Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1AutomaticScaling $automaticScaling)
  {
    $this->automaticScaling = $automaticScaling;
  }
  /**
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1AutomaticScaling
   */
  public function getAutomaticScaling()
  {
    return $this->automaticScaling;
  }
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setDeploymentUri($deploymentUri)
  {
    $this->deploymentUri = $deploymentUri;
  }
  public function getDeploymentUri()
  {
    return $this->deploymentUri;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setErrorMessage($errorMessage)
  {
    $this->errorMessage = $errorMessage;
  }
  public function getErrorMessage()
  {
    return $this->errorMessage;
  }
  public function setIsDefault($isDefault)
  {
    $this->isDefault = $isDefault;
  }
  public function getIsDefault()
  {
    return $this->isDefault;
  }
  public function setLastUseTime($lastUseTime)
  {
    $this->lastUseTime = $lastUseTime;
  }
  public function getLastUseTime()
  {
    return $this->lastUseTime;
  }
  /**
   * @param Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1ManualScaling
   */
  public function setManualScaling(Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1ManualScaling $manualScaling)
  {
    $this->manualScaling = $manualScaling;
  }
  /**
   * @return Google_Service_CloudMachineLearningEngine_GoogleCloudMlV1beta1ManualScaling
   */
  public function getManualScaling()
  {
    return $this->manualScaling;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setRuntimeVersion($runtimeVersion)
  {
    $this->runtimeVersion = $runtimeVersion;
  }
  public function getRuntimeVersion()
  {
    return $this->runtimeVersion;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
}
