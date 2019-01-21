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

class Google_Service_DLP_GooglePrivacyDlpV2CryptoKey extends Google_Model
{
  protected $kmsWrappedType = 'Google_Service_DLP_GooglePrivacyDlpV2KmsWrappedCryptoKey';
  protected $kmsWrappedDataType = '';
  protected $transientType = 'Google_Service_DLP_GooglePrivacyDlpV2TransientCryptoKey';
  protected $transientDataType = '';
  protected $unwrappedType = 'Google_Service_DLP_GooglePrivacyDlpV2UnwrappedCryptoKey';
  protected $unwrappedDataType = '';

  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2KmsWrappedCryptoKey
   */
  public function setKmsWrapped(Google_Service_DLP_GooglePrivacyDlpV2KmsWrappedCryptoKey $kmsWrapped)
  {
    $this->kmsWrapped = $kmsWrapped;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2KmsWrappedCryptoKey
   */
  public function getKmsWrapped()
  {
    return $this->kmsWrapped;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2TransientCryptoKey
   */
  public function setTransient(Google_Service_DLP_GooglePrivacyDlpV2TransientCryptoKey $transient)
  {
    $this->transient = $transient;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2TransientCryptoKey
   */
  public function getTransient()
  {
    return $this->transient;
  }
  /**
   * @param Google_Service_DLP_GooglePrivacyDlpV2UnwrappedCryptoKey
   */
  public function setUnwrapped(Google_Service_DLP_GooglePrivacyDlpV2UnwrappedCryptoKey $unwrapped)
  {
    $this->unwrapped = $unwrapped;
  }
  /**
   * @return Google_Service_DLP_GooglePrivacyDlpV2UnwrappedCryptoKey
   */
  public function getUnwrapped()
  {
    return $this->unwrapped;
  }
}
