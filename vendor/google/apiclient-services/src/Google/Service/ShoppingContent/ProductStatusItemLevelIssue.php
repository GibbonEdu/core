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

class Google_Service_ShoppingContent_ProductStatusItemLevelIssue extends Google_Model
{
  public $attributeName;
  public $code;
  public $description;
  public $destination;
  public $detail;
  public $documentation;
  public $resolution;
  public $servability;

  public function setAttributeName($attributeName)
  {
    $this->attributeName = $attributeName;
  }
  public function getAttributeName()
  {
    return $this->attributeName;
  }
  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setDetail($detail)
  {
    $this->detail = $detail;
  }
  public function getDetail()
  {
    return $this->detail;
  }
  public function setDocumentation($documentation)
  {
    $this->documentation = $documentation;
  }
  public function getDocumentation()
  {
    return $this->documentation;
  }
  public function setResolution($resolution)
  {
    $this->resolution = $resolution;
  }
  public function getResolution()
  {
    return $this->resolution;
  }
  public function setServability($servability)
  {
    $this->servability = $servability;
  }
  public function getServability()
  {
    return $this->servability;
  }
}
