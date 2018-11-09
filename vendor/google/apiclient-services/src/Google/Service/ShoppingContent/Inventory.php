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

class Google_Service_ShoppingContent_Inventory extends Google_Model
{
  public $availability;
  public $customLabel0;
  public $customLabel1;
  public $customLabel2;
  public $customLabel3;
  public $customLabel4;
  protected $installmentType = 'Google_Service_ShoppingContent_Installment';
  protected $installmentDataType = '';
  public $instoreProductLocation;
  public $kind;
  protected $loyaltyPointsType = 'Google_Service_ShoppingContent_LoyaltyPoints';
  protected $loyaltyPointsDataType = '';
  protected $pickupType = 'Google_Service_ShoppingContent_InventoryPickup';
  protected $pickupDataType = '';
  protected $priceType = 'Google_Service_ShoppingContent_Price';
  protected $priceDataType = '';
  public $quantity;
  protected $salePriceType = 'Google_Service_ShoppingContent_Price';
  protected $salePriceDataType = '';
  public $salePriceEffectiveDate;
  public $sellOnGoogleQuantity;

  public function setAvailability($availability)
  {
    $this->availability = $availability;
  }
  public function getAvailability()
  {
    return $this->availability;
  }
  public function setCustomLabel0($customLabel0)
  {
    $this->customLabel0 = $customLabel0;
  }
  public function getCustomLabel0()
  {
    return $this->customLabel0;
  }
  public function setCustomLabel1($customLabel1)
  {
    $this->customLabel1 = $customLabel1;
  }
  public function getCustomLabel1()
  {
    return $this->customLabel1;
  }
  public function setCustomLabel2($customLabel2)
  {
    $this->customLabel2 = $customLabel2;
  }
  public function getCustomLabel2()
  {
    return $this->customLabel2;
  }
  public function setCustomLabel3($customLabel3)
  {
    $this->customLabel3 = $customLabel3;
  }
  public function getCustomLabel3()
  {
    return $this->customLabel3;
  }
  public function setCustomLabel4($customLabel4)
  {
    $this->customLabel4 = $customLabel4;
  }
  public function getCustomLabel4()
  {
    return $this->customLabel4;
  }
  /**
   * @param Google_Service_ShoppingContent_Installment
   */
  public function setInstallment(Google_Service_ShoppingContent_Installment $installment)
  {
    $this->installment = $installment;
  }
  /**
   * @return Google_Service_ShoppingContent_Installment
   */
  public function getInstallment()
  {
    return $this->installment;
  }
  public function setInstoreProductLocation($instoreProductLocation)
  {
    $this->instoreProductLocation = $instoreProductLocation;
  }
  public function getInstoreProductLocation()
  {
    return $this->instoreProductLocation;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param Google_Service_ShoppingContent_LoyaltyPoints
   */
  public function setLoyaltyPoints(Google_Service_ShoppingContent_LoyaltyPoints $loyaltyPoints)
  {
    $this->loyaltyPoints = $loyaltyPoints;
  }
  /**
   * @return Google_Service_ShoppingContent_LoyaltyPoints
   */
  public function getLoyaltyPoints()
  {
    return $this->loyaltyPoints;
  }
  /**
   * @param Google_Service_ShoppingContent_InventoryPickup
   */
  public function setPickup(Google_Service_ShoppingContent_InventoryPickup $pickup)
  {
    $this->pickup = $pickup;
  }
  /**
   * @return Google_Service_ShoppingContent_InventoryPickup
   */
  public function getPickup()
  {
    return $this->pickup;
  }
  /**
   * @param Google_Service_ShoppingContent_Price
   */
  public function setPrice(Google_Service_ShoppingContent_Price $price)
  {
    $this->price = $price;
  }
  /**
   * @return Google_Service_ShoppingContent_Price
   */
  public function getPrice()
  {
    return $this->price;
  }
  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;
  }
  public function getQuantity()
  {
    return $this->quantity;
  }
  /**
   * @param Google_Service_ShoppingContent_Price
   */
  public function setSalePrice(Google_Service_ShoppingContent_Price $salePrice)
  {
    $this->salePrice = $salePrice;
  }
  /**
   * @return Google_Service_ShoppingContent_Price
   */
  public function getSalePrice()
  {
    return $this->salePrice;
  }
  public function setSalePriceEffectiveDate($salePriceEffectiveDate)
  {
    $this->salePriceEffectiveDate = $salePriceEffectiveDate;
  }
  public function getSalePriceEffectiveDate()
  {
    return $this->salePriceEffectiveDate;
  }
  public function setSellOnGoogleQuantity($sellOnGoogleQuantity)
  {
    $this->sellOnGoogleQuantity = $sellOnGoogleQuantity;
  }
  public function getSellOnGoogleQuantity()
  {
    return $this->sellOnGoogleQuantity;
  }
}
