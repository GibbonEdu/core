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

class Google_Service_Analytics_AccountTreeRequest extends Google_Model
{
  public $accountName;
  protected $accountSettingsType = 'Google_Service_Analytics_AccountTreeRequestAccountSettings';
  protected $accountSettingsDataType = '';
  public $kind;
  public $profileName;
  public $timezone;
  public $webpropertyName;
  public $websiteUrl;

  public function setAccountName($accountName)
  {
    $this->accountName = $accountName;
  }
  public function getAccountName()
  {
    return $this->accountName;
  }
  /**
   * @param Google_Service_Analytics_AccountTreeRequestAccountSettings
   */
  public function setAccountSettings(Google_Service_Analytics_AccountTreeRequestAccountSettings $accountSettings)
  {
    $this->accountSettings = $accountSettings;
  }
  /**
   * @return Google_Service_Analytics_AccountTreeRequestAccountSettings
   */
  public function getAccountSettings()
  {
    return $this->accountSettings;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setProfileName($profileName)
  {
    $this->profileName = $profileName;
  }
  public function getProfileName()
  {
    return $this->profileName;
  }
  public function setTimezone($timezone)
  {
    $this->timezone = $timezone;
  }
  public function getTimezone()
  {
    return $this->timezone;
  }
  public function setWebpropertyName($webpropertyName)
  {
    $this->webpropertyName = $webpropertyName;
  }
  public function getWebpropertyName()
  {
    return $this->webpropertyName;
  }
  public function setWebsiteUrl($websiteUrl)
  {
    $this->websiteUrl = $websiteUrl;
  }
  public function getWebsiteUrl()
  {
    return $this->websiteUrl;
  }
}
