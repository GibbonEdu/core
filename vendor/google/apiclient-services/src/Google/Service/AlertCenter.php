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
 * Service definition for AlertCenter (v1beta1).
 *
 * <p>
 * G Suite Alert Center API to view and manage alerts on issues affecting your
 * domain.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/admin-sdk/alertcenter/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_AlertCenter extends Google_Service
{


  public $alerts;
  public $alerts_feedback;
  
  /**
   * Constructs the internal representation of the AlertCenter service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://alertcenter.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1beta1';
    $this->serviceName = 'alertcenter';

    $this->alerts = new Google_Service_AlertCenter_Resource_Alerts(
        $this,
        $this->serviceName,
        'alerts',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'v1beta1/alerts/{alertId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'alertId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'v1beta1/alerts/{alertId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'alertId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'v1beta1/alerts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'orderBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->alerts_feedback = new Google_Service_AlertCenter_Resource_AlertsFeedback(
        $this,
        $this->serviceName,
        'feedback',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1beta1/alerts/{alertId}/feedback',
              'httpMethod' => 'POST',
              'parameters' => array(
                'alertId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'v1beta1/alerts/{alertId}/feedback',
              'httpMethod' => 'GET',
              'parameters' => array(
                'alertId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
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
