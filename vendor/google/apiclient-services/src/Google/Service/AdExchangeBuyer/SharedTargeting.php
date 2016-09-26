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

class Google_Service_AdExchangeBuyer_SharedTargeting extends Google_Collection
{
  protected $collection_key = 'inclusions';
  protected $exclusionsType = 'Google_Service_AdExchangeBuyer_TargetingValue';
  protected $exclusionsDataType = 'array';
  protected $inclusionsType = 'Google_Service_AdExchangeBuyer_TargetingValue';
  protected $inclusionsDataType = 'array';
  public $key;

  public function setExclusions($exclusions)
  {
    $this->exclusions = $exclusions;
  }
  public function getExclusions()
  {
    return $this->exclusions;
  }
  public function setInclusions($inclusions)
  {
    $this->inclusions = $inclusions;
  }
  public function getInclusions()
  {
    return $this->inclusions;
  }
  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
  }
}
