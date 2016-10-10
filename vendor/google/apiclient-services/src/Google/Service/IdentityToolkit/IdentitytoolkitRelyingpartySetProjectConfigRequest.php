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

class Google_Service_IdentityToolkit_IdentitytoolkitRelyingpartySetProjectConfigRequest extends Google_Collection
{
  protected $collection_key = 'idpConfig';
  public $allowPasswordUser;
  public $apiKey;
  public $authorizedDomains;
  protected $changeEmailTemplateType = 'Google_Service_IdentityToolkit_EmailTemplate';
  protected $changeEmailTemplateDataType = '';
  public $delegatedProjectNumber;
  public $enableAnonymousUser;
  protected $idpConfigType = 'Google_Service_IdentityToolkit_IdpConfig';
  protected $idpConfigDataType = 'array';
  protected $legacyResetPasswordTemplateType = 'Google_Service_IdentityToolkit_EmailTemplate';
  protected $legacyResetPasswordTemplateDataType = '';
  protected $resetPasswordTemplateType = 'Google_Service_IdentityToolkit_EmailTemplate';
  protected $resetPasswordTemplateDataType = '';
  public $useEmailSending;
  protected $verifyEmailTemplateType = 'Google_Service_IdentityToolkit_EmailTemplate';
  protected $verifyEmailTemplateDataType = '';

  public function setAllowPasswordUser($allowPasswordUser)
  {
    $this->allowPasswordUser = $allowPasswordUser;
  }
  public function getAllowPasswordUser()
  {
    return $this->allowPasswordUser;
  }
  public function setApiKey($apiKey)
  {
    $this->apiKey = $apiKey;
  }
  public function getApiKey()
  {
    return $this->apiKey;
  }
  public function setAuthorizedDomains($authorizedDomains)
  {
    $this->authorizedDomains = $authorizedDomains;
  }
  public function getAuthorizedDomains()
  {
    return $this->authorizedDomains;
  }
  public function setChangeEmailTemplate(Google_Service_IdentityToolkit_EmailTemplate $changeEmailTemplate)
  {
    $this->changeEmailTemplate = $changeEmailTemplate;
  }
  public function getChangeEmailTemplate()
  {
    return $this->changeEmailTemplate;
  }
  public function setDelegatedProjectNumber($delegatedProjectNumber)
  {
    $this->delegatedProjectNumber = $delegatedProjectNumber;
  }
  public function getDelegatedProjectNumber()
  {
    return $this->delegatedProjectNumber;
  }
  public function setEnableAnonymousUser($enableAnonymousUser)
  {
    $this->enableAnonymousUser = $enableAnonymousUser;
  }
  public function getEnableAnonymousUser()
  {
    return $this->enableAnonymousUser;
  }
  public function setIdpConfig($idpConfig)
  {
    $this->idpConfig = $idpConfig;
  }
  public function getIdpConfig()
  {
    return $this->idpConfig;
  }
  public function setLegacyResetPasswordTemplate(Google_Service_IdentityToolkit_EmailTemplate $legacyResetPasswordTemplate)
  {
    $this->legacyResetPasswordTemplate = $legacyResetPasswordTemplate;
  }
  public function getLegacyResetPasswordTemplate()
  {
    return $this->legacyResetPasswordTemplate;
  }
  public function setResetPasswordTemplate(Google_Service_IdentityToolkit_EmailTemplate $resetPasswordTemplate)
  {
    $this->resetPasswordTemplate = $resetPasswordTemplate;
  }
  public function getResetPasswordTemplate()
  {
    return $this->resetPasswordTemplate;
  }
  public function setUseEmailSending($useEmailSending)
  {
    $this->useEmailSending = $useEmailSending;
  }
  public function getUseEmailSending()
  {
    return $this->useEmailSending;
  }
  public function setVerifyEmailTemplate(Google_Service_IdentityToolkit_EmailTemplate $verifyEmailTemplate)
  {
    $this->verifyEmailTemplate = $verifyEmailTemplate;
  }
  public function getVerifyEmailTemplate()
  {
    return $this->verifyEmailTemplate;
  }
}
