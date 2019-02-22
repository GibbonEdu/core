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

class Google_Service_ShoppingContent_UnitInvoice extends Google_Collection
{
  protected $collection_key = 'unitPriceTaxes';
  protected $additionalChargesType = 'Google_Service_ShoppingContent_UnitInvoiceAdditionalCharge';
  protected $additionalChargesDataType = 'array';
  protected $promotionsType = 'Google_Service_ShoppingContent_Promotion';
  protected $promotionsDataType = 'array';
  protected $unitPricePretaxType = 'Google_Service_ShoppingContent_Price';
  protected $unitPricePretaxDataType = '';
  protected $unitPriceTaxesType = 'Google_Service_ShoppingContent_UnitInvoiceTaxLine';
  protected $unitPriceTaxesDataType = 'array';

  /**
   * @param Google_Service_ShoppingContent_UnitInvoiceAdditionalCharge
   */
  public function setAdditionalCharges($additionalCharges)
  {
    $this->additionalCharges = $additionalCharges;
  }
  /**
   * @return Google_Service_ShoppingContent_UnitInvoiceAdditionalCharge
   */
  public function getAdditionalCharges()
  {
    return $this->additionalCharges;
  }
  /**
   * @param Google_Service_ShoppingContent_Promotion
   */
  public function setPromotions($promotions)
  {
    $this->promotions = $promotions;
  }
  /**
   * @return Google_Service_ShoppingContent_Promotion
   */
  public function getPromotions()
  {
    return $this->promotions;
  }
  /**
   * @param Google_Service_ShoppingContent_Price
   */
  public function setUnitPricePretax(Google_Service_ShoppingContent_Price $unitPricePretax)
  {
    $this->unitPricePretax = $unitPricePretax;
  }
  /**
   * @return Google_Service_ShoppingContent_Price
   */
  public function getUnitPricePretax()
  {
    return $this->unitPricePretax;
  }
  /**
   * @param Google_Service_ShoppingContent_UnitInvoiceTaxLine
   */
  public function setUnitPriceTaxes($unitPriceTaxes)
  {
    $this->unitPriceTaxes = $unitPriceTaxes;
  }
  /**
   * @return Google_Service_ShoppingContent_UnitInvoiceTaxLine
   */
  public function getUnitPriceTaxes()
  {
    return $this->unitPriceTaxes;
  }
}
