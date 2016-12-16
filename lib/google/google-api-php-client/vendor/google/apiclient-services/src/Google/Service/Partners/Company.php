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

class Google_Service_Partners_Company extends Google_Collection
{
  protected $collection_key = 'services';
  protected $certificationStatusesType = 'Google_Service_Partners_CertificationStatus';
  protected $certificationStatusesDataType = 'array';
  protected $convertedMinMonthlyBudgetType = 'Google_Service_Partners_Money';
  protected $convertedMinMonthlyBudgetDataType = '';
  public $id;
  public $industries;
  protected $localizedInfosType = 'Google_Service_Partners_LocalizedCompanyInfo';
  protected $localizedInfosDataType = 'array';
  protected $locationsType = 'Google_Service_Partners_Location';
  protected $locationsDataType = 'array';
  public $name;
  protected $originalMinMonthlyBudgetType = 'Google_Service_Partners_Money';
  protected $originalMinMonthlyBudgetDataType = '';
  protected $publicProfileType = 'Google_Service_Partners_PublicProfile';
  protected $publicProfileDataType = '';
  protected $ranksType = 'Google_Service_Partners_Rank';
  protected $ranksDataType = 'array';
  public $services;
  public $websiteUrl;

  public function setCertificationStatuses($certificationStatuses)
  {
    $this->certificationStatuses = $certificationStatuses;
  }
  public function getCertificationStatuses()
  {
    return $this->certificationStatuses;
  }
  public function setConvertedMinMonthlyBudget(Google_Service_Partners_Money $convertedMinMonthlyBudget)
  {
    $this->convertedMinMonthlyBudget = $convertedMinMonthlyBudget;
  }
  public function getConvertedMinMonthlyBudget()
  {
    return $this->convertedMinMonthlyBudget;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIndustries($industries)
  {
    $this->industries = $industries;
  }
  public function getIndustries()
  {
    return $this->industries;
  }
  public function setLocalizedInfos($localizedInfos)
  {
    $this->localizedInfos = $localizedInfos;
  }
  public function getLocalizedInfos()
  {
    return $this->localizedInfos;
  }
  public function setLocations($locations)
  {
    $this->locations = $locations;
  }
  public function getLocations()
  {
    return $this->locations;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOriginalMinMonthlyBudget(Google_Service_Partners_Money $originalMinMonthlyBudget)
  {
    $this->originalMinMonthlyBudget = $originalMinMonthlyBudget;
  }
  public function getOriginalMinMonthlyBudget()
  {
    return $this->originalMinMonthlyBudget;
  }
  public function setPublicProfile(Google_Service_Partners_PublicProfile $publicProfile)
  {
    $this->publicProfile = $publicProfile;
  }
  public function getPublicProfile()
  {
    return $this->publicProfile;
  }
  public function setRanks($ranks)
  {
    $this->ranks = $ranks;
  }
  public function getRanks()
  {
    return $this->ranks;
  }
  public function setServices($services)
  {
    $this->services = $services;
  }
  public function getServices()
  {
    return $this->services;
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
