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

class Google_Service_AndroidEnterprise_ManagedConfigurationsSettings extends Google_Collection
{
  protected $collection_key = 'managedProperty';
  public $kind;
  public $lastUpdatedTimestampMillis;
  protected $managedPropertyType = 'Google_Service_AndroidEnterprise_ManagedProperty';
  protected $managedPropertyDataType = 'array';
  public $mcmId;
  public $name;

  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastUpdatedTimestampMillis($lastUpdatedTimestampMillis)
  {
    $this->lastUpdatedTimestampMillis = $lastUpdatedTimestampMillis;
  }
  public function getLastUpdatedTimestampMillis()
  {
    return $this->lastUpdatedTimestampMillis;
  }
  /**
   * @param Google_Service_AndroidEnterprise_ManagedProperty
   */
  public function setManagedProperty($managedProperty)
  {
    $this->managedProperty = $managedProperty;
  }
  /**
   * @return Google_Service_AndroidEnterprise_ManagedProperty
   */
  public function getManagedProperty()
  {
    return $this->managedProperty;
  }
  public function setMcmId($mcmId)
  {
    $this->mcmId = $mcmId;
  }
  public function getMcmId()
  {
    return $this->mcmId;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}
