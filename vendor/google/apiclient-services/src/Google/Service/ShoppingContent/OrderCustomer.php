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

class Google_Service_ShoppingContent_OrderCustomer extends Google_Model
{
  public $email;
  public $fullName;
  protected $marketingRightsInfoType = 'Google_Service_ShoppingContent_OrderCustomerMarketingRightsInfo';
  protected $marketingRightsInfoDataType = '';

  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFullName($fullName)
  {
    $this->fullName = $fullName;
  }
  public function getFullName()
  {
    return $this->fullName;
  }
  /**
   * @param Google_Service_ShoppingContent_OrderCustomerMarketingRightsInfo
   */
  public function setMarketingRightsInfo(Google_Service_ShoppingContent_OrderCustomerMarketingRightsInfo $marketingRightsInfo)
  {
    $this->marketingRightsInfo = $marketingRightsInfo;
  }
  /**
   * @return Google_Service_ShoppingContent_OrderCustomerMarketingRightsInfo
   */
  public function getMarketingRightsInfo()
  {
    return $this->marketingRightsInfo;
  }
}
