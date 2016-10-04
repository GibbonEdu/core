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

class Google_Service_AdExchangeBuyer_PricePerBuyer extends Google_Model
{
  public $auctionTier;
  protected $buyerType = 'Google_Service_AdExchangeBuyer_Buyer';
  protected $buyerDataType = '';
  protected $priceType = 'Google_Service_AdExchangeBuyer_Price';
  protected $priceDataType = '';

  public function setAuctionTier($auctionTier)
  {
    $this->auctionTier = $auctionTier;
  }
  public function getAuctionTier()
  {
    return $this->auctionTier;
  }
  public function setBuyer(Google_Service_AdExchangeBuyer_Buyer $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setPrice(Google_Service_AdExchangeBuyer_Price $price)
  {
    $this->price = $price;
  }
  public function getPrice()
  {
    return $this->price;
  }
}
