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

class Google_Service_Dfareporting_Browser extends Google_Model
{
  public $browserVersionId;
  public $dartId;
  public $kind;
  public $majorVersion;
  public $minorVersion;
  public $name;

  public function setBrowserVersionId($browserVersionId)
  {
    $this->browserVersionId = $browserVersionId;
  }
  public function getBrowserVersionId()
  {
    return $this->browserVersionId;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMajorVersion($majorVersion)
  {
    $this->majorVersion = $majorVersion;
  }
  public function getMajorVersion()
  {
    return $this->majorVersion;
  }
  public function setMinorVersion($minorVersion)
  {
    $this->minorVersion = $minorVersion;
  }
  public function getMinorVersion()
  {
    return $this->minorVersion;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}
