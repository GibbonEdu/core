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

class Google_Service_Dfareporting_CreativeAssetMetadata extends Google_Collection
{
  protected $collection_key = 'warnedValidationRules';
  protected $assetIdentifierType = 'Google_Service_Dfareporting_CreativeAssetId';
  protected $assetIdentifierDataType = '';
  protected $clickTagsType = 'Google_Service_Dfareporting_ClickTag';
  protected $clickTagsDataType = 'array';
  public $detectedFeatures;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  public $warnedValidationRules;

  public function setAssetIdentifier(Google_Service_Dfareporting_CreativeAssetId $assetIdentifier)
  {
    $this->assetIdentifier = $assetIdentifier;
  }
  public function getAssetIdentifier()
  {
    return $this->assetIdentifier;
  }
  public function setClickTags($clickTags)
  {
    $this->clickTags = $clickTags;
  }
  public function getClickTags()
  {
    return $this->clickTags;
  }
  public function setDetectedFeatures($detectedFeatures)
  {
    $this->detectedFeatures = $detectedFeatures;
  }
  public function getDetectedFeatures()
  {
    return $this->detectedFeatures;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setWarnedValidationRules($warnedValidationRules)
  {
    $this->warnedValidationRules = $warnedValidationRules;
  }
  public function getWarnedValidationRules()
  {
    return $this->warnedValidationRules;
  }
}
