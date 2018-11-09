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

class Google_Service_ShoppingContent_Amount extends Google_Model
{
  protected $pretaxType = 'Google_Service_ShoppingContent_Price';
  protected $pretaxDataType = '';
  protected $taxType = 'Google_Service_ShoppingContent_Price';
  protected $taxDataType = '';

  /**
   * @param Google_Service_ShoppingContent_Price
   */
  public function setPretax(Google_Service_ShoppingContent_Price $pretax)
  {
    $this->pretax = $pretax;
  }
  /**
   * @return Google_Service_ShoppingContent_Price
   */
  public function getPretax()
  {
    return $this->pretax;
  }
  /**
   * @param Google_Service_ShoppingContent_Price
   */
  public function setTax(Google_Service_ShoppingContent_Price $tax)
  {
    $this->tax = $tax;
  }
  /**
   * @return Google_Service_ShoppingContent_Price
   */
  public function getTax()
  {
    return $this->tax;
  }
}
