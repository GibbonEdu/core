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
 * The "metrics" collection of methods.
 * Typical usage is:
 *  <code>
 *   $loggingService = new Google_Service_Logging(...);
 *   $metrics = $loggingService->metrics;
 *  </code>
 */
class Google_Service_Logging_Resource_ProjectsMetrics extends Google_Service_Resource
{
  /**
   * Creates a logs-based metric. (metrics.create)
   *
   * @param string $parent The resource name of the project in which to create the
   * metric. Example: `"projects/my-project-id"`.
   *
   * The new metric must be provided in the request.
   * @param Google_Service_Logging_LogMetric $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Logging_LogMetric
   */
  public function create($parent, Google_Service_Logging_LogMetric $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Logging_LogMetric");
  }
  /**
   * Deletes a logs-based metric. (metrics.delete)
   *
   * @param string $metricName The resource name of the metric to delete. Example:
   * `"projects/my-project-id/metrics/my-metric-id"`.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Logging_LoggingEmpty
   */
  public function delete($metricName, $optParams = array())
  {
    $params = array('metricName' => $metricName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Logging_LoggingEmpty");
  }
  /**
   * Gets a logs-based metric. (metrics.get)
   *
   * @param string $metricName The resource name of the desired metric. Example:
   * `"projects/my-project-id/metrics/my-metric-id"`.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Logging_LogMetric
   */
  public function get($metricName, $optParams = array())
  {
    $params = array('metricName' => $metricName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Logging_LogMetric");
  }
  /**
   * Lists logs-based metrics. (metrics.listProjectsMetrics)
   *
   * @param string $parent Required. The resource name containing the metrics.
   * Example: `"projects/my-project-id"`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. The maximum number of results to return
   * from this request. Non-positive values are ignored.  The presence of
   * `nextPageToken` in the response indicates that more results might be
   * available.
   * @opt_param string pageToken Optional. If present, then retrieve the next
   * batch of results from the preceding call to this method.  `pageToken` must be
   * the value of `nextPageToken` from the previous response.  The values of other
   * method parameters should be identical to those in the previous call.
   * @return Google_Service_Logging_ListLogMetricsResponse
   */
  public function listProjectsMetrics($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Logging_ListLogMetricsResponse");
  }
  /**
   * Creates or updates a logs-based metric. (metrics.update)
   *
   * @param string $metricName The resource name of the metric to update. Example:
   * `"projects/my-project-id/metrics/my-metric-id"`.
   *
   * The updated metric must be provided in the request and have the same
   * identifier that is specified in `metricName`. If the metric does not exist,
   * it is created.
   * @param Google_Service_Logging_LogMetric $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Logging_LogMetric
   */
  public function update($metricName, Google_Service_Logging_LogMetric $postBody, $optParams = array())
  {
    $params = array('metricName' => $metricName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Logging_LogMetric");
  }
}
