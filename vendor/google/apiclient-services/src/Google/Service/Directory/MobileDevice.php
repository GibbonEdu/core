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

class Google_Service_Directory_MobileDevice extends Google_Collection
{
  protected $collection_key = 'otherAccountsInfo';
  public $adbStatus;
  protected $applicationsType = 'Google_Service_Directory_MobileDeviceApplications';
  protected $applicationsDataType = 'array';
  public $basebandVersion;
  public $buildNumber;
  public $defaultLanguage;
  public $developerOptionsStatus;
  public $deviceCompromisedStatus;
  public $deviceId;
  public $email;
  public $etag;
  public $firstSync;
  public $hardwareId;
  public $imei;
  public $kernelVersion;
  public $kind;
  public $lastSync;
  public $managedAccountIsOnOwnerProfile;
  public $meid;
  public $model;
  public $name;
  public $networkOperator;
  public $os;
  public $otherAccountsInfo;
  public $resourceId;
  public $serialNumber;
  public $status;
  public $supportsWorkProfile;
  public $type;
  public $unknownSourcesStatus;
  public $userAgent;
  public $wifiMacAddress;

  public function setAdbStatus($adbStatus)
  {
    $this->adbStatus = $adbStatus;
  }
  public function getAdbStatus()
  {
    return $this->adbStatus;
  }
  public function setApplications($applications)
  {
    $this->applications = $applications;
  }
  public function getApplications()
  {
    return $this->applications;
  }
  public function setBasebandVersion($basebandVersion)
  {
    $this->basebandVersion = $basebandVersion;
  }
  public function getBasebandVersion()
  {
    return $this->basebandVersion;
  }
  public function setBuildNumber($buildNumber)
  {
    $this->buildNumber = $buildNumber;
  }
  public function getBuildNumber()
  {
    return $this->buildNumber;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDeveloperOptionsStatus($developerOptionsStatus)
  {
    $this->developerOptionsStatus = $developerOptionsStatus;
  }
  public function getDeveloperOptionsStatus()
  {
    return $this->developerOptionsStatus;
  }
  public function setDeviceCompromisedStatus($deviceCompromisedStatus)
  {
    $this->deviceCompromisedStatus = $deviceCompromisedStatus;
  }
  public function getDeviceCompromisedStatus()
  {
    return $this->deviceCompromisedStatus;
  }
  public function setDeviceId($deviceId)
  {
    $this->deviceId = $deviceId;
  }
  public function getDeviceId()
  {
    return $this->deviceId;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFirstSync($firstSync)
  {
    $this->firstSync = $firstSync;
  }
  public function getFirstSync()
  {
    return $this->firstSync;
  }
  public function setHardwareId($hardwareId)
  {
    $this->hardwareId = $hardwareId;
  }
  public function getHardwareId()
  {
    return $this->hardwareId;
  }
  public function setImei($imei)
  {
    $this->imei = $imei;
  }
  public function getImei()
  {
    return $this->imei;
  }
  public function setKernelVersion($kernelVersion)
  {
    $this->kernelVersion = $kernelVersion;
  }
  public function getKernelVersion()
  {
    return $this->kernelVersion;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastSync($lastSync)
  {
    $this->lastSync = $lastSync;
  }
  public function getLastSync()
  {
    return $this->lastSync;
  }
  public function setManagedAccountIsOnOwnerProfile($managedAccountIsOnOwnerProfile)
  {
    $this->managedAccountIsOnOwnerProfile = $managedAccountIsOnOwnerProfile;
  }
  public function getManagedAccountIsOnOwnerProfile()
  {
    return $this->managedAccountIsOnOwnerProfile;
  }
  public function setMeid($meid)
  {
    $this->meid = $meid;
  }
  public function getMeid()
  {
    return $this->meid;
  }
  public function setModel($model)
  {
    $this->model = $model;
  }
  public function getModel()
  {
    return $this->model;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNetworkOperator($networkOperator)
  {
    $this->networkOperator = $networkOperator;
  }
  public function getNetworkOperator()
  {
    return $this->networkOperator;
  }
  public function setOs($os)
  {
    $this->os = $os;
  }
  public function getOs()
  {
    return $this->os;
  }
  public function setOtherAccountsInfo($otherAccountsInfo)
  {
    $this->otherAccountsInfo = $otherAccountsInfo;
  }
  public function getOtherAccountsInfo()
  {
    return $this->otherAccountsInfo;
  }
  public function setResourceId($resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setSerialNumber($serialNumber)
  {
    $this->serialNumber = $serialNumber;
  }
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSupportsWorkProfile($supportsWorkProfile)
  {
    $this->supportsWorkProfile = $supportsWorkProfile;
  }
  public function getSupportsWorkProfile()
  {
    return $this->supportsWorkProfile;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUnknownSourcesStatus($unknownSourcesStatus)
  {
    $this->unknownSourcesStatus = $unknownSourcesStatus;
  }
  public function getUnknownSourcesStatus()
  {
    return $this->unknownSourcesStatus;
  }
  public function setUserAgent($userAgent)
  {
    $this->userAgent = $userAgent;
  }
  public function getUserAgent()
  {
    return $this->userAgent;
  }
  public function setWifiMacAddress($wifiMacAddress)
  {
    $this->wifiMacAddress = $wifiMacAddress;
  }
  public function getWifiMacAddress()
  {
    return $this->wifiMacAddress;
  }
}
