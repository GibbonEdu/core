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

class Google_Service_Analytics_EntityAdWordsLink extends Google_Collection
{
  protected $collection_key = 'profileIds';
  protected $adWordsAccountsType = 'Google_Service_Analytics_AdWordsAccount';
  protected $adWordsAccountsDataType = 'array';
  protected $entityType = 'Google_Service_Analytics_EntityAdWordsLinkEntity';
  protected $entityDataType = '';
  public $id;
  public $kind;
  public $name;
  public $profileIds;
  public $selfLink;

  public function setAdWordsAccounts($adWordsAccounts)
  {
    $this->adWordsAccounts = $adWordsAccounts;
  }
  public function getAdWordsAccounts()
  {
    return $this->adWordsAccounts;
  }
  public function setEntity(Google_Service_Analytics_EntityAdWordsLinkEntity $entity)
  {
    $this->entity = $entity;
  }
  public function getEntity()
  {
    return $this->entity;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setProfileIds($profileIds)
  {
    $this->profileIds = $profileIds;
  }
  public function getProfileIds()
  {
    return $this->profileIds;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}
