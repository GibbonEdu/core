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

class Google_Service_Dfareporting_FloodlightConfiguration extends Google_Collection
{
  protected $collection_key = 'userDefinedVariableConfigurations';
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $analyticsDataSharingEnabled;
  public $exposureToConversionEnabled;
  public $firstDayOfWeek;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $inAppAttributionTrackingEnabled;
  public $kind;
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  public $naturalSearchConversionAttributionOption;
  protected $omnitureSettingsType = 'Google_Service_Dfareporting_OmnitureSettings';
  protected $omnitureSettingsDataType = '';
  public $standardVariableTypes;
  public $subaccountId;
  protected $tagSettingsType = 'Google_Service_Dfareporting_TagSettings';
  protected $tagSettingsDataType = '';
  protected $thirdPartyAuthenticationTokensType = 'Google_Service_Dfareporting_ThirdPartyAuthenticationToken';
  protected $thirdPartyAuthenticationTokensDataType = 'array';
  protected $userDefinedVariableConfigurationsType = 'Google_Service_Dfareporting_UserDefinedVariableConfiguration';
  protected $userDefinedVariableConfigurationsDataType = 'array';

  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setAnalyticsDataSharingEnabled($analyticsDataSharingEnabled)
  {
    $this->analyticsDataSharingEnabled = $analyticsDataSharingEnabled;
  }
  public function getAnalyticsDataSharingEnabled()
  {
    return $this->analyticsDataSharingEnabled;
  }
  public function setExposureToConversionEnabled($exposureToConversionEnabled)
  {
    $this->exposureToConversionEnabled = $exposureToConversionEnabled;
  }
  public function getExposureToConversionEnabled()
  {
    return $this->exposureToConversionEnabled;
  }
  public function setFirstDayOfWeek($firstDayOfWeek)
  {
    $this->firstDayOfWeek = $firstDayOfWeek;
  }
  public function getFirstDayOfWeek()
  {
    return $this->firstDayOfWeek;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setInAppAttributionTrackingEnabled($inAppAttributionTrackingEnabled)
  {
    $this->inAppAttributionTrackingEnabled = $inAppAttributionTrackingEnabled;
  }
  public function getInAppAttributionTrackingEnabled()
  {
    return $this->inAppAttributionTrackingEnabled;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setNaturalSearchConversionAttributionOption($naturalSearchConversionAttributionOption)
  {
    $this->naturalSearchConversionAttributionOption = $naturalSearchConversionAttributionOption;
  }
  public function getNaturalSearchConversionAttributionOption()
  {
    return $this->naturalSearchConversionAttributionOption;
  }
  public function setOmnitureSettings(Google_Service_Dfareporting_OmnitureSettings $omnitureSettings)
  {
    $this->omnitureSettings = $omnitureSettings;
  }
  public function getOmnitureSettings()
  {
    return $this->omnitureSettings;
  }
  public function setStandardVariableTypes($standardVariableTypes)
  {
    $this->standardVariableTypes = $standardVariableTypes;
  }
  public function getStandardVariableTypes()
  {
    return $this->standardVariableTypes;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTagSettings(Google_Service_Dfareporting_TagSettings $tagSettings)
  {
    $this->tagSettings = $tagSettings;
  }
  public function getTagSettings()
  {
    return $this->tagSettings;
  }
  public function setThirdPartyAuthenticationTokens($thirdPartyAuthenticationTokens)
  {
    $this->thirdPartyAuthenticationTokens = $thirdPartyAuthenticationTokens;
  }
  public function getThirdPartyAuthenticationTokens()
  {
    return $this->thirdPartyAuthenticationTokens;
  }
  public function setUserDefinedVariableConfigurations($userDefinedVariableConfigurations)
  {
    $this->userDefinedVariableConfigurations = $userDefinedVariableConfigurations;
  }
  public function getUserDefinedVariableConfigurations()
  {
    return $this->userDefinedVariableConfigurations;
  }
}
