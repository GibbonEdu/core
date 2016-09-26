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

class Google_Service_Dfareporting_RichMediaExitOverride extends Google_Model
{
  protected $clickThroughUrlType = 'Google_Service_Dfareporting_ClickThroughUrl';
  protected $clickThroughUrlDataType = '';
  public $enabled;
  public $exitId;

  public function setClickThroughUrl(Google_Service_Dfareporting_ClickThroughUrl $clickThroughUrl)
  {
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl()
  {
    return $this->clickThroughUrl;
  }
  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  public function getEnabled()
  {
    return $this->enabled;
  }
  public function setExitId($exitId)
  {
    $this->exitId = $exitId;
  }
  public function getExitId()
  {
    return $this->exitId;
  }
}
