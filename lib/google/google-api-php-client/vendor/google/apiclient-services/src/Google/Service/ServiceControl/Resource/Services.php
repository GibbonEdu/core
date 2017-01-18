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
 * The "services" collection of methods.
 * Typical usage is:
 *  <code>
 *   $servicecontrolService = new Google_Service_ServiceControl(...);
 *   $services = $servicecontrolService->services;
 *  </code>
 */
class Google_Service_ServiceControl_Resource_Services extends Google_Service_Resource
{
  /**
   * Checks an operation with Google Service Control to decide whether the given
   * operation should proceed. It should be called before the operation is
   * executed.
   *
   * This method requires the `servicemanagement.services.check` permission on the
   * specified service. For more information, see [Google Cloud
   * IAM](https://cloud.google.com/iam). (services.check)
   *
   * @param string $serviceName The service name as specified in its service
   * configuration. For example, `"pubsub.googleapis.com"`.
   *
   * See google.api.Service for the definition of a service name.
   * @param Google_Service_ServiceControl_CheckRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ServiceControl_CheckResponse
   */
  public function check($serviceName, Google_Service_ServiceControl_CheckRequest $postBody, $optParams = array())
  {
    $params = array('serviceName' => $serviceName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('check', array($params), "Google_Service_ServiceControl_CheckResponse");
  }
  /**
   * Reports operations to Google Service Control. It should be called after the
   * operation is completed.
   *
   * This method requires the `servicemanagement.services.report` permission on
   * the specified service. For more information, see [Google Cloud
   * IAM](https://cloud.google.com/iam). (services.report)
   *
   * @param string $serviceName The service name as specified in its service
   * configuration. For example, `"pubsub.googleapis.com"`.
   *
   * See google.api.Service for the definition of a service name.
   * @param Google_Service_ServiceControl_ReportRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ServiceControl_ReportResponse
   */
  public function report($serviceName, Google_Service_ServiceControl_ReportRequest $postBody, $optParams = array())
  {
    $params = array('serviceName' => $serviceName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('report', array($params), "Google_Service_ServiceControl_ReportResponse");
  }
}
