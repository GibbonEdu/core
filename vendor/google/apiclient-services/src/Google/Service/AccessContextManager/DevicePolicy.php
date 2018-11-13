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

class Google_Service_AccessContextManager_DevicePolicy extends Google_Collection
{
  protected $collection_key = 'osConstraints';
  public $allowedDeviceManagementLevels;
  public $allowedEncryptionStatuses;
  protected $osConstraintsType = 'Google_Service_AccessContextManager_OsConstraint';
  protected $osConstraintsDataType = 'array';
  public $requireScreenlock;

  public function setAllowedDeviceManagementLevels($allowedDeviceManagementLevels)
  {
    $this->allowedDeviceManagementLevels = $allowedDeviceManagementLevels;
  }
  public function getAllowedDeviceManagementLevels()
  {
    return $this->allowedDeviceManagementLevels;
  }
  public function setAllowedEncryptionStatuses($allowedEncryptionStatuses)
  {
    $this->allowedEncryptionStatuses = $allowedEncryptionStatuses;
  }
  public function getAllowedEncryptionStatuses()
  {
    return $this->allowedEncryptionStatuses;
  }
  /**
   * @param Google_Service_AccessContextManager_OsConstraint
   */
  public function setOsConstraints($osConstraints)
  {
    $this->osConstraints = $osConstraints;
  }
  /**
   * @return Google_Service_AccessContextManager_OsConstraint
   */
  public function getOsConstraints()
  {
    return $this->osConstraints;
  }
  public function setRequireScreenlock($requireScreenlock)
  {
    $this->requireScreenlock = $requireScreenlock;
  }
  public function getRequireScreenlock()
  {
    return $this->requireScreenlock;
  }
}
