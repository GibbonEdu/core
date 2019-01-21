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

class Google_Service_CloudBuild_BuiltImage extends Google_Model
{
  public $digest;
  public $name;
  protected $pushTimingType = 'Google_Service_CloudBuild_TimeSpan';
  protected $pushTimingDataType = '';

  public function setDigest($digest)
  {
    $this->digest = $digest;
  }
  public function getDigest()
  {
    return $this->digest;
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
  public function setPushTiming(Google_Service_CloudBuild_TimeSpan $pushTiming)
  {
    $this->pushTiming = $pushTiming;
  }
  /**
   * @return Google_Service_CloudBuild_TimeSpan
   */
  public function getPushTiming()
  {
    return $this->pushTiming;
  }
}
