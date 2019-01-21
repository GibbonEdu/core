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

class Google_Service_ShoppingContent_LiasettingsCustomBatchResponseEntry extends Google_Collection
{
  protected $collection_key = 'posDataProviders';
  public $batchId;
  protected $errorsType = 'Google_Service_ShoppingContent_Errors';
  protected $errorsDataType = '';
  protected $gmbAccountsType = 'Google_Service_ShoppingContent_GmbAccounts';
  protected $gmbAccountsDataType = '';
  public $kind;
  protected $liaSettingsType = 'Google_Service_ShoppingContent_LiaSettings';
  protected $liaSettingsDataType = '';
  protected $posDataProvidersType = 'Google_Service_ShoppingContent_PosDataProviders';
  protected $posDataProvidersDataType = 'array';

  public function setBatchId($batchId)
  {
    $this->batchId = $batchId;
  }
  public function getBatchId()
  {
    return $this->batchId;
  }
  /**
   * @param Google_Service_ShoppingContent_Errors
   */
  public function setErrors(Google_Service_ShoppingContent_Errors $errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return Google_Service_ShoppingContent_Errors
   */
  public function getErrors()
  {
    return $this->errors;
  }
  /**
   * @param Google_Service_ShoppingContent_GmbAccounts
   */
  public function setGmbAccounts(Google_Service_ShoppingContent_GmbAccounts $gmbAccounts)
  {
    $this->gmbAccounts = $gmbAccounts;
  }
  /**
   * @return Google_Service_ShoppingContent_GmbAccounts
   */
  public function getGmbAccounts()
  {
    return $this->gmbAccounts;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param Google_Service_ShoppingContent_LiaSettings
   */
  public function setLiaSettings(Google_Service_ShoppingContent_LiaSettings $liaSettings)
  {
    $this->liaSettings = $liaSettings;
  }
  /**
   * @return Google_Service_ShoppingContent_LiaSettings
   */
  public function getLiaSettings()
  {
    return $this->liaSettings;
  }
  /**
   * @param Google_Service_ShoppingContent_PosDataProviders
   */
  public function setPosDataProviders($posDataProviders)
  {
    $this->posDataProviders = $posDataProviders;
  }
  /**
   * @return Google_Service_ShoppingContent_PosDataProviders
   */
  public function getPosDataProviders()
  {
    return $this->posDataProviders;
  }
}
