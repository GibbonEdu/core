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

class Google_Service_Dfareporting_RemarketingList extends Google_Model
{
  public $accountId;
  public $active;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $description;
  public $id;
  public $kind;
  public $lifeSpan;
  protected $listPopulationRuleType = 'Google_Service_Dfareporting_ListPopulationRule';
  protected $listPopulationRuleDataType = '';
  public $listSize;
  public $listSource;
  public $name;
  public $subaccountId;

  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
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
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLifeSpan($lifeSpan)
  {
    $this->lifeSpan = $lifeSpan;
  }
  public function getLifeSpan()
  {
    return $this->lifeSpan;
  }
  public function setListPopulationRule(Google_Service_Dfareporting_ListPopulationRule $listPopulationRule)
  {
    $this->listPopulationRule = $listPopulationRule;
  }
  public function getListPopulationRule()
  {
    return $this->listPopulationRule;
  }
  public function setListSize($listSize)
  {
    $this->listSize = $listSize;
  }
  public function getListSize()
  {
    return $this->listSize;
  }
  public function setListSource($listSource)
  {
    $this->listSource = $listSource;
  }
  public function getListSource()
  {
    return $this->listSource;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}
