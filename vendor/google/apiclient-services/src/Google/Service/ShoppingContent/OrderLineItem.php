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

class Google_Service_ShoppingContent_OrderLineItem extends Google_Collection
{
  protected $collection_key = 'returns';
  protected $cancellationsType = 'Google_Service_ShoppingContent_OrderCancellation';
  protected $cancellationsDataType = 'array';
  public $id;
  protected $priceType = 'Google_Service_ShoppingContent_Price';
  protected $priceDataType = '';
  protected $productType = 'Google_Service_ShoppingContent_OrderLineItemProduct';
  protected $productDataType = '';
  public $quantityCanceled;
  public $quantityDelivered;
  public $quantityOrdered;
  public $quantityPending;
  public $quantityReturned;
  public $quantityShipped;
  protected $returnInfoType = 'Google_Service_ShoppingContent_OrderLineItemReturnInfo';
  protected $returnInfoDataType = '';
  protected $returnsType = 'Google_Service_ShoppingContent_OrderReturn';
  protected $returnsDataType = 'array';
  protected $shippingDetailsType = 'Google_Service_ShoppingContent_OrderLineItemShippingDetails';
  protected $shippingDetailsDataType = '';
  protected $taxType = 'Google_Service_ShoppingContent_Price';
  protected $taxDataType = '';

  public function setCancellations($cancellations)
  {
    $this->cancellations = $cancellations;
  }
  public function getCancellations()
  {
    return $this->cancellations;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setPrice(Google_Service_ShoppingContent_Price $price)
  {
    $this->price = $price;
  }
  public function getPrice()
  {
    return $this->price;
  }
  public function setProduct(Google_Service_ShoppingContent_OrderLineItemProduct $product)
  {
    $this->product = $product;
  }
  public function getProduct()
  {
    return $this->product;
  }
  public function setQuantityCanceled($quantityCanceled)
  {
    $this->quantityCanceled = $quantityCanceled;
  }
  public function getQuantityCanceled()
  {
    return $this->quantityCanceled;
  }
  public function setQuantityDelivered($quantityDelivered)
  {
    $this->quantityDelivered = $quantityDelivered;
  }
  public function getQuantityDelivered()
  {
    return $this->quantityDelivered;
  }
  public function setQuantityOrdered($quantityOrdered)
  {
    $this->quantityOrdered = $quantityOrdered;
  }
  public function getQuantityOrdered()
  {
    return $this->quantityOrdered;
  }
  public function setQuantityPending($quantityPending)
  {
    $this->quantityPending = $quantityPending;
  }
  public function getQuantityPending()
  {
    return $this->quantityPending;
  }
  public function setQuantityReturned($quantityReturned)
  {
    $this->quantityReturned = $quantityReturned;
  }
  public function getQuantityReturned()
  {
    return $this->quantityReturned;
  }
  public function setQuantityShipped($quantityShipped)
  {
    $this->quantityShipped = $quantityShipped;
  }
  public function getQuantityShipped()
  {
    return $this->quantityShipped;
  }
  public function setReturnInfo(Google_Service_ShoppingContent_OrderLineItemReturnInfo $returnInfo)
  {
    $this->returnInfo = $returnInfo;
  }
  public function getReturnInfo()
  {
    return $this->returnInfo;
  }
  public function setReturns($returns)
  {
    $this->returns = $returns;
  }
  public function getReturns()
  {
    return $this->returns;
  }
  public function setShippingDetails(Google_Service_ShoppingContent_OrderLineItemShippingDetails $shippingDetails)
  {
    $this->shippingDetails = $shippingDetails;
  }
  public function getShippingDetails()
  {
    return $this->shippingDetails;
  }
  public function setTax(Google_Service_ShoppingContent_Price $tax)
  {
    $this->tax = $tax;
  }
  public function getTax()
  {
    return $this->tax;
  }
}
