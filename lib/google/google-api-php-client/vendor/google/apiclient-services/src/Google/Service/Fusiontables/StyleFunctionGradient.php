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

class Google_Service_Fusiontables_StyleFunctionGradient extends Google_Collection
{
  protected $collection_key = 'colors';
  protected $colorsType = 'Google_Service_Fusiontables_StyleFunctionGradientColors';
  protected $colorsDataType = 'array';
  public $max;
  public $min;

  public function setColors($colors)
  {
    $this->colors = $colors;
  }
  public function getColors()
  {
    return $this->colors;
  }
  public function setMax($max)
  {
    $this->max = $max;
  }
  public function getMax()
  {
    return $this->max;
  }
  public function setMin($min)
  {
    $this->min = $min;
  }
  public function getMin()
  {
    return $this->min;
  }
}
