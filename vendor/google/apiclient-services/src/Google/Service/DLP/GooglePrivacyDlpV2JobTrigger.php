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

class Google_Service_DLP_GooglePrivacyDlpV2JobTrigger extends Google_Collection
{
  protected $collection_key = 'triggers';
  public $createTime;
  public $description;
  public $displayName;
  protected $errorsType = 'Google_Service_DLP_GooglePrivacyDlpV2Error';
  protected $errorsDataType = 'array';
  protected $inspectJobType = 'Google_Service_DLP_GooglePrivacyDlpV2InspectJobConfig';
  protected $inspectJobDataType = '';
  public $lastRunTime;
  public $name;
  public $status;
  protected $triggersType = 'Google_Service_DLP_GooglePrivacyDlpV2Trigger';
  protected $triggersDataType = 'array';
  public $updateTime;

  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  public function getCreateTime()
  {
    return $this->createTime;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2Error
   */
  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2Error
   */
  public function getErrors()
  {
    return $this->errors;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2InspectJobConfig
   */
  public function setInspectJob(Google_Service_DLP_GooglePrivacyDlpV2InspectJobConfig $inspectJob)
  {
    $this->inspectJob = $inspectJob;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2InspectJobConfig
   */
  public function getInspectJob()
  {
    return $this->inspectJob;
  }
  public function setLastRunTime($lastRunTime)
  {
    $this->lastRunTime = $lastRunTime;
  }
  public function getLastRunTime()
  {
    return $this->lastRunTime;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2Trigger
   */
  public function setTriggers($triggers)
  {
    $this->triggers = $triggers;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2Trigger
   */
  public function getTriggers()
  {
    return $this->triggers;
  }
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}
