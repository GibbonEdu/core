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

class Google_Service_DLP_GooglePrivacyDlpV2CryptoReplaceFfxFpeConfig extends Google_Model
{
  public $commonAlphabet;
  protected $contextType = 'Google_Service_DLP_GooglePrivacyDlpV2FieldId';
  protected $contextDataType = '';
  protected $cryptoKeyType = 'Google_Service_DLP_GooglePrivacyDlpV2CryptoKey';
  protected $cryptoKeyDataType = '';
  public $customAlphabet;
  public $radix;
  protected $surrogateInfoTypeType = 'Google_Service_DLP_GooglePrivacyDlpV2InfoType';
  protected $surrogateInfoTypeDataType = '';

  public function setCommonAlphabet($commonAlphabet)
  {
    $this->commonAlphabet = $commonAlphabet;
  }
  public function getCommonAlphabet()
  {
    return $this->commonAlphabet;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2FieldId
   */
  public function setContext(Google_Service_DLP_GooglePrivacyDlpV2FieldId $context)
  {
    $this->context = $context;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2FieldId
   */
  public function getContext()
  {
    return $this->context;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2CryptoKey
   */
  public function setCryptoKey(Google_Service_DLP_GooglePrivacyDlpV2CryptoKey $cryptoKey)
  {
    $this->cryptoKey = $cryptoKey;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2CryptoKey
   */
  public function getCryptoKey()
  {
    return $this->cryptoKey;
  }
  public function setCustomAlphabet($customAlphabet)
  {
    $this->customAlphabet = $customAlphabet;
  }
  public function getCustomAlphabet()
  {
    return $this->customAlphabet;
  }
  public function setRadix($radix)
  {
    $this->radix = $radix;
  }
  public function getRadix()
  {
    return $this->radix;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2InfoType
   */
  public function setSurrogateInfoType(Google_Service_DLP_GooglePrivacyDlpV2InfoType $surrogateInfoType)
  {
    $this->surrogateInfoType = $surrogateInfoType;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2InfoType
   */
  public function getSurrogateInfoType()
  {
    return $this->surrogateInfoType;
  }
}
