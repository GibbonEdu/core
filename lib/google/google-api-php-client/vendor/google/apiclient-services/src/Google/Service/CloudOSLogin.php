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

/**
 * Service definition for CloudOSLogin (v1alpha).
 *
 * <p>
 * A Google Cloud API for managing OS login configuration for Directory API
 * users.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/apis-explorer/#p/oslogin/v1alpha/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_CloudOSLogin extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";

  public $users;
  public $users_sshPublicKeys;
  
  /**
   * Constructs the internal representation of the CloudOSLogin service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://oslogin.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1alpha';
    $this->serviceName = 'oslogin';

    $this->users = new Google_Service_CloudOSLogin_Resource_Users(
        $this,
        $this->serviceName,
        'users',
        array(
          'methods' => array(
            'getLoginProfile' => array(
              'path' => 'v1alpha/{+name}/loginProfile',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'importSshPublicKey' => array(
              'path' => 'v1alpha/{+parent}:importSshPublicKey',
              'httpMethod' => 'POST',
              'parameters' => array(
                'parent' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->users_sshPublicKeys = new Google_Service_CloudOSLogin_Resource_UsersSshPublicKeys(
        $this,
        $this->serviceName,
        'sshPublicKeys',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'v1alpha/{+name}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1alpha/{+name}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'v1alpha/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'updateMask' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
}
