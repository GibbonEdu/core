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

class Google_Service_Dfareporting_TechnologyTargeting extends Google_Collection
{
  protected $collection_key = 'platformTypes';
  protected $browsersType = 'Google_Service_Dfareporting_Browser';
  protected $browsersDataType = 'array';
  protected $connectionTypesType = 'Google_Service_Dfareporting_ConnectionType';
  protected $connectionTypesDataType = 'array';
  protected $mobileCarriersType = 'Google_Service_Dfareporting_MobileCarrier';
  protected $mobileCarriersDataType = 'array';
  protected $operatingSystemVersionsType = 'Google_Service_Dfareporting_OperatingSystemVersion';
  protected $operatingSystemVersionsDataType = 'array';
  protected $operatingSystemsType = 'Google_Service_Dfareporting_OperatingSystem';
  protected $operatingSystemsDataType = 'array';
  protected $platformTypesType = 'Google_Service_Dfareporting_PlatformType';
  protected $platformTypesDataType = 'array';

  public function setBrowsers($browsers)
  {
    $this->browsers = $browsers;
  }
  public function getBrowsers()
  {
    return $this->browsers;
  }
  public function setConnectionTypes($connectionTypes)
  {
    $this->connectionTypes = $connectionTypes;
  }
  public function getConnectionTypes()
  {
    return $this->connectionTypes;
  }
  public function setMobileCarriers($mobileCarriers)
  {
    $this->mobileCarriers = $mobileCarriers;
  }
  public function getMobileCarriers()
  {
    return $this->mobileCarriers;
  }
  public function setOperatingSystemVersions($operatingSystemVersions)
  {
    $this->operatingSystemVersions = $operatingSystemVersions;
  }
  public function getOperatingSystemVersions()
  {
    return $this->operatingSystemVersions;
  }
  public function setOperatingSystems($operatingSystems)
  {
    $this->operatingSystems = $operatingSystems;
  }
  public function getOperatingSystems()
  {
    return $this->operatingSystems;
  }
  public function setPlatformTypes($platformTypes)
  {
    $this->platformTypes = $platformTypes;
  }
  public function getPlatformTypes()
  {
    return $this->platformTypes;
  }
}
