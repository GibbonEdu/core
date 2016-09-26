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

/**
 * Service definition for Logging (v2beta1).
 *
 * <p>
 * Writes log entries and manages your logs, log sinks, and logs-based metrics.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://cloud.google.com/logging/docs/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Logging extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** Administrate log data for your projects. */
  const LOGGING_ADMIN =
      "https://www.googleapis.com/auth/logging.admin";
  /** View log data for your projects. */
  const LOGGING_READ =
      "https://www.googleapis.com/auth/logging.read";
  /** Submit log data for your projects. */
  const LOGGING_WRITE =
      "https://www.googleapis.com/auth/logging.write";

  public $entries;
  public $monitoredResourceDescriptors;
  public $projects_logs;
  public $projects_metrics;
  public $projects_sinks;
  
  /**
   * Constructs the internal representation of the Logging service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://logging.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v2beta1';
    $this->serviceName = 'logging';

    $this->entries = new Google_Service_Logging_Resource_Entries(
        $this,
        $this->serviceName,
        'entries',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v2beta1/entries:list',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'write' => array(
              'path' => 'v2beta1/entries:write',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->monitoredResourceDescriptors = new Google_Service_Logging_Resource_MonitoredResourceDescriptors(
        $this,
        $this->serviceName,
        'monitoredResourceDescriptors',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v2beta1/monitoredResourceDescriptors',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->projects_logs = new Google_Service_Logging_Resource_ProjectsLogs(
        $this,
        $this->serviceName,
        'logs',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'v2beta1/{+logName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'logName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->projects_metrics = new Google_Service_Logging_Resource_ProjectsMetrics(
        $this,
        $this->serviceName,
        'metrics',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v2beta1/{+projectName}/metrics',
              'httpMethod' => 'POST',
              'parameters' => array(
                'projectName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'v2beta1/{+metricName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'metricName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v2beta1/{+metricName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'metricName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v2beta1/{+projectName}/metrics',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'update' => array(
              'path' => 'v2beta1/{+metricName}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'metricName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->projects_sinks = new Google_Service_Logging_Resource_ProjectsSinks(
        $this,
        $this->serviceName,
        'sinks',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v2beta1/{+projectName}/sinks',
              'httpMethod' => 'POST',
              'parameters' => array(
                'projectName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'v2beta1/{+sinkName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'sinkName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v2beta1/{+sinkName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'sinkName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v2beta1/{+projectName}/sinks',
              'httpMethod' => 'GET',
              'parameters' => array(
                'projectName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'update' => array(
              'path' => 'v2beta1/{+sinkName}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'sinkName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
  }
}
