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
 * Service definition for ServiceNetworking (v1beta).
 *
 * <p>
 * Provides automatic management of network configurations necessary for certain
 * services.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/service-infrastructure/docs/service-networking/getting-started" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_ServiceNetworking extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** Manage your Google API service configuration. */
  const SERVICE_MANAGEMENT =
      "https://www.googleapis.com/auth/service.management";

  public $operations;
  public $services;
  public $services_connections;
  
  /**
   * Constructs the internal representation of the ServiceNetworking service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://servicenetworking.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1beta';
    $this->serviceName = 'servicenetworking';

    $this->operations = new Google_Service_ServiceNetworking_Resource_Operations(
        $this,
        $this->serviceName,
        'operations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1beta/{+name}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'name' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->services = new Google_Service_ServiceNetworking_Resource_Services(
        $this,
        $this->serviceName,
        'services',
        array(
          'methods' => array(
            'addSubnetwork' => array(
              'path' => 'v1beta/{+parent}:addSubnetwork',
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
    $this->services_connections = new Google_Service_ServiceNetworking_Resource_ServicesConnections(
        $this,
        $this->serviceName,
        'connections',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1beta/{+parent}/connections',
              'httpMethod' => 'POST',
              'parameters' => array(
                'parent' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1beta/{+parent}/connections',
              'httpMethod' => 'GET',
              'parameters' => array(
                'parent' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'network' => array(
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
