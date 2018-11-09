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

class Google_Service_ShoppingContent_UnitInvoiceAdditionalCharge extends Google_Collection
{
  protected $collection_key = 'additionalChargePromotions';
  protected $additionalChargeAmountType = 'Google_Service_ShoppingContent_Amount';
  protected $additionalChargeAmountDataType = '';
  protected $additionalChargePromotionsType = 'Google_Service_ShoppingContent_Promotion';
  protected $additionalChargePromotionsDataType = 'array';
  public $type;

  /**
   * @param Google_Service_ShoppingContent_Amount
   */
  public function setAdditionalChargeAmount(Google_Service_ShoppingContent_Amount $additionalChargeAmount)
  {
    $this->additionalChargeAmount = $additionalChargeAmount;
  }
  /**
   * @return Google_Service_ShoppingContent_Amount
   */
  public function getAdditionalChargeAmount()
  {
    return $this->additionalChargeAmount;
  }
  /**
   * @param Google_Service_ShoppingContent_Promotion
   */
  public function setAdditionalChargePromotions($additionalChargePromotions)
  {
    $this->additionalChargePromotions = $additionalChargePromotions;
  }
  /**
   * @return Google_Service_ShoppingContent_Promotion
   */
  public function getAdditionalChargePromotions()
  {
    return $this->additionalChargePromotions;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}
