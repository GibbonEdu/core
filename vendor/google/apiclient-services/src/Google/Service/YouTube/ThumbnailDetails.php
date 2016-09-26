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

class Google_Service_YouTube_ThumbnailDetails extends Google_Model
{
  protected $defaultType = 'Google_Service_YouTube_Thumbnail';
  protected $defaultDataType = '';
  protected $highType = 'Google_Service_YouTube_Thumbnail';
  protected $highDataType = '';
  protected $maxresType = 'Google_Service_YouTube_Thumbnail';
  protected $maxresDataType = '';
  protected $mediumType = 'Google_Service_YouTube_Thumbnail';
  protected $mediumDataType = '';
  protected $standardType = 'Google_Service_YouTube_Thumbnail';
  protected $standardDataType = '';

  public function setDefault(Google_Service_YouTube_Thumbnail $default)
  {
    $this->default = $default;
  }
  public function getDefault()
  {
    return $this->default;
  }
  public function setHigh(Google_Service_YouTube_Thumbnail $high)
  {
    $this->high = $high;
  }
  public function getHigh()
  {
    return $this->high;
  }
  public function setMaxres(Google_Service_YouTube_Thumbnail $maxres)
  {
    $this->maxres = $maxres;
  }
  public function getMaxres()
  {
    return $this->maxres;
  }
  public function setMedium(Google_Service_YouTube_Thumbnail $medium)
  {
    $this->medium = $medium;
  }
  public function getMedium()
  {
    return $this->medium;
  }
  public function setStandard(Google_Service_YouTube_Thumbnail $standard)
  {
    $this->standard = $standard;
  }
  public function getStandard()
  {
    return $this->standard;
  }
}
