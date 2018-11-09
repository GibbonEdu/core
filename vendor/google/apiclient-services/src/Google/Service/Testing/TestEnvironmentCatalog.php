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

class Google_Service_Testing_TestEnvironmentCatalog extends Google_Model
{
  protected $androidDeviceCatalogType = 'Google_Service_Testing_AndroidDeviceCatalog';
  protected $androidDeviceCatalogDataType = '';
  protected $iosDeviceCatalogType = 'Google_Service_Testing_IosDeviceCatalog';
  protected $iosDeviceCatalogDataType = '';
  protected $networkConfigurationCatalogType = 'Google_Service_Testing_NetworkConfigurationCatalog';
  protected $networkConfigurationCatalogDataType = '';
  protected $softwareCatalogType = 'Google_Service_Testing_ProvidedSoftwareCatalog';
  protected $softwareCatalogDataType = '';

  /**
   * @param Google_Service_Testing_AndroidDeviceCatalog
   */
  public function setAndroidDeviceCatalog(Google_Service_Testing_AndroidDeviceCatalog $androidDeviceCatalog)
  {
    $this->androidDeviceCatalog = $androidDeviceCatalog;
  }
  /**
   * @return Google_Service_Testing_AndroidDeviceCatalog
   */
  public function getAndroidDeviceCatalog()
  {
    return $this->androidDeviceCatalog;
  }
  /**
   * @param Google_Service_Testing_IosDeviceCatalog
   */
  public function setIosDeviceCatalog(Google_Service_Testing_IosDeviceCatalog $iosDeviceCatalog)
  {
    $this->iosDeviceCatalog = $iosDeviceCatalog;
  }
  /**
   * @return Google_Service_Testing_IosDeviceCatalog
   */
  public function getIosDeviceCatalog()
  {
    return $this->iosDeviceCatalog;
  }
  /**
   * @param Google_Service_Testing_NetworkConfigurationCatalog
   */
  public function setNetworkConfigurationCatalog(Google_Service_Testing_NetworkConfigurationCatalog $networkConfigurationCatalog)
  {
    $this->networkConfigurationCatalog = $networkConfigurationCatalog;
  }
  /**
   * @return Google_Service_Testing_NetworkConfigurationCatalog
   */
  public function getNetworkConfigurationCatalog()
  {
    return $this->networkConfigurationCatalog;
  }
  /**
   * @param Google_Service_Testing_ProvidedSoftwareCatalog
   */
  public function setSoftwareCatalog(Google_Service_Testing_ProvidedSoftwareCatalog $softwareCatalog)
  {
    $this->softwareCatalog = $softwareCatalog;
  }
  /**
   * @return Google_Service_Testing_ProvidedSoftwareCatalog
   */
  public function getSoftwareCatalog()
  {
    return $this->softwareCatalog;
  }
}
