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

class Google_Service_Partners_Lead extends Google_Collection
{
  protected $collection_key = 'gpsMotivations';
  public $comments;
  public $email;
  public $familyName;
  public $givenName;
  public $gpsMotivations;
  public $id;
  protected $minMonthlyBudgetType = 'Google_Service_Partners_Money';
  protected $minMonthlyBudgetDataType = '';
  public $phoneNumber;
  public $type;
  public $websiteUrl;

  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFamilyName($familyName)
  {
    $this->familyName = $familyName;
  }
  public function getFamilyName()
  {
    return $this->familyName;
  }
  public function setGivenName($givenName)
  {
    $this->givenName = $givenName;
  }
  public function getGivenName()
  {
    return $this->givenName;
  }
  public function setGpsMotivations($gpsMotivations)
  {
    $this->gpsMotivations = $gpsMotivations;
  }
  public function getGpsMotivations()
  {
    return $this->gpsMotivations;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setMinMonthlyBudget(Google_Service_Partners_Money $minMonthlyBudget)
  {
    $this->minMonthlyBudget = $minMonthlyBudget;
  }
  public function getMinMonthlyBudget()
  {
    return $this->minMonthlyBudget;
  }
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
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
