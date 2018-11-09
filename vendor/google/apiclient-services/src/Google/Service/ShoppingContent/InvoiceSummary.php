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

class Google_Service_ShoppingContent_InvoiceSummary extends Google_Collection
{
  protected $collection_key = 'promotionSummaries';
  protected $additionalChargeSummariesType = 'Google_Service_ShoppingContent_InvoiceSummaryAdditionalChargeSummary';
  protected $additionalChargeSummariesDataType = 'array';
  protected $customerBalanceType = 'Google_Service_ShoppingContent_Amount';
  protected $customerBalanceDataType = '';
  protected $googleBalanceType = 'Google_Service_ShoppingContent_Amount';
  protected $googleBalanceDataType = '';
  protected $merchantBalanceType = 'Google_Service_ShoppingContent_Amount';
  protected $merchantBalanceDataType = '';
  protected $productTotalType = 'Google_Service_ShoppingContent_Amount';
  protected $productTotalDataType = '';
  protected $promotionSummariesType = 'Google_Service_ShoppingContent_Promotion';
  protected $promotionSummariesDataType = 'array';

  /**
   * @param Google_Service_ShoppingContent_InvoiceSummaryAdditionalChargeSummary
   */
  public function setAdditionalChargeSummaries($additionalChargeSummaries)
  {
    $this->additionalChargeSummaries = $additionalChargeSummaries;
  }
  /**
   * @return Google_Service_ShoppingContent_InvoiceSummaryAdditionalChargeSummary
   */
  public function getAdditionalChargeSummaries()
  {
    return $this->additionalChargeSummaries;
  }
  /**
   * @param Google_Service_ShoppingContent_Amount
   */
  public function setCustomerBalance(Google_Service_ShoppingContent_Amount $customerBalance)
  {
    $this->customerBalance = $customerBalance;
  }
  /**
   * @return Google_Service_ShoppingContent_Amount
   */
  public function getCustomerBalance()
  {
    return $this->customerBalance;
  }
  /**
   * @param Google_Service_ShoppingContent_Amount
   */
  public function setGoogleBalance(Google_Service_ShoppingContent_Amount $googleBalance)
  {
    $this->googleBalance = $googleBalance;
  }
  /**
   * @return Google_Service_ShoppingContent_Amount
   */
  public function getGoogleBalance()
  {
    return $this->googleBalance;
  }
  /**
   * @param Google_Service_ShoppingContent_Amount
   */
  public function setMerchantBalance(Google_Service_ShoppingContent_Amount $merchantBalance)
  {
    $this->merchantBalance = $merchantBalance;
  }
  /**
   * @return Google_Service_ShoppingContent_Amount
   */
  public function getMerchantBalance()
  {
    return $this->merchantBalance;
  }
  /**
   * @param Google_Service_ShoppingContent_Amount
   */
  public function setProductTotal(Google_Service_ShoppingContent_Amount $productTotal)
  {
    $this->productTotal = $productTotal;
  }
  /**
   * @return Google_Service_ShoppingContent_Amount
   */
  public function getProductTotal()
  {
    return $this->productTotal;
  }
  /**
   * @param Google_Service_ShoppingContent_Promotion
   */
  public function setPromotionSummaries($promotionSummaries)
  {
    $this->promotionSummaries = $promotionSummaries;
  }
  /**
   * @return Google_Service_ShoppingContent_Promotion
   */
  public function getPromotionSummaries()
  {
    return $this->promotionSummaries;
  }
}
