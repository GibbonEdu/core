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

class Google_Service_Testing_Environment extends Google_Model
{
  protected $androidDeviceType = 'Google_Service_Testing_AndroidDevice';
  protected $androidDeviceDataType = '';
  protected $iosDeviceType = 'Google_Service_Testing_IosDevice';
  protected $iosDeviceDataType = '';

  /**
   * @param Google_Service_Testing_AndroidDevice
   */
  public function setAndroidDevice(Google_Service_Testing_AndroidDevice $androidDevice)
  {
    $this->androidDevice = $androidDevice;
  }
  /**
   * @return Google_Service_Testing_AndroidDevice
   */
  public function getAndroidDevice()
  {
    return $this->androidDevice;
  }
  /**
   * @param Google_Service_Testing_IosDevice
   */
  public function setIosDevice(Google_Service_Testing_IosDevice $iosDevice)
  {
    $this->iosDevice = $iosDevice;
  }
  /**
   * @return Google_Service_Testing_IosDevice
   */
  public function getIosDevice()
  {
    return $this->iosDevice;
  }
}
