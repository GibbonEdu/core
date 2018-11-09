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

class Google_Service_ShoppingContent_OrderpaymentsNotifyRefundRequest extends Google_Collection
{
  protected $collection_key = 'invoiceIds';
  public $invoiceId;
  public $invoiceIds;
  public $refundState;

  public function setInvoiceId($invoiceId)
  {
    $this->invoiceId = $invoiceId;
  }
  public function getInvoiceId()
  {
    return $this->invoiceId;
  }
  public function setInvoiceIds($invoiceIds)
  {
    $this->invoiceIds = $invoiceIds;
  }
  public function getInvoiceIds()
  {
    return $this->invoiceIds;
  }
  public function setRefundState($refundState)
  {
    $this->refundState = $refundState;
  }
  public function getRefundState()
  {
    return $this->refundState;
  }
}
