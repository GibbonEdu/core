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
 * The "timeseries" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudmonitoringService = new Google_Service_CloudMonitoring(...);
 *   $timeseries = $cloudmonitoringService->timeseries;
 *  </code>
 */
class Google_Service_CloudMonitoring_Resource_Timeseries extends Google_Service_Resource
{
  /**
   * List the data points of the time series that match the metric and labels
   * values and that have data points in the interval. Large responses are
   * paginated; use the nextPageToken returned in the response to request
   * subsequent pages of results by setting the pageToken query parameter to the
   * value of the nextPageToken. (timeseries.listTimeseries)
   *
   * @param string $project The project ID to which this time series belongs. The
   * value can be the numeric project ID or string-based project name.
   * @param string $metric Metric names are protocol-free URLs as listed in the
   * Supported Metrics page. For example,
   * compute.googleapis.com/instance/disk/read_ops_count.
   * @param string $youngest End of the time interval (inclusive), which is
   * expressed as an RFC 3339 timestamp.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string aggregator The aggregation function that will reduce the
   * data points in each window to a single point. This parameter is only valid
   * for non-cumulative metrics with a value type of INT64 or DOUBLE.
   * @opt_param int count Maximum number of data points per page, which is used
   * for pagination of results.
   * @opt_param string labels A collection of labels for the matching time series,
   * which are represented as: - key==value: key equals the value  - key=~value:
   * key regex matches the value  - key!=value: key does not equal the value  -
   * key!~value: key regex does not match the value  For example, to list all of
   * the time series descriptors for the region us-central1, you could specify:
   * label=cloud.googleapis.com%2Flocation=~us-central1.*
   * @opt_param string oldest Start of the time interval (exclusive), which is
   * expressed as an RFC 3339 timestamp. If neither oldest nor timespan is
   * specified, the default time interval will be (youngest - 4 hours, youngest]
   * @opt_param string pageToken The pagination token, which is used to page
   * through large result sets. Set this value to the value of the nextPageToken
   * to retrieve the next page of results.
   * @opt_param string timespan Length of the time interval to query, which is an
   * alternative way to declare the interval: (youngest - timespan, youngest]. The
   * timespan and oldest parameters should not be used together. Units: - s:
   * second  - m: minute  - h: hour  - d: day  - w: week  Examples: 2s, 3m, 4w.
   * Only one unit is allowed, for example: 2w3d is not allowed; you should use
   * 17d instead.
   *
   * If neither oldest nor timespan is specified, the default time interval will
   * be (youngest - 4 hours, youngest].
   * @opt_param string window The sampling window. At most one data point will be
   * returned for each window in the requested time interval. This parameter is
   * only valid for non-cumulative metric types. Units: - m: minute  - h: hour  -
   * d: day  - w: week  Examples: 3m, 4w. Only one unit is allowed, for example:
   * 2w3d is not allowed; you should use 17d instead.
   * @return Google_Service_CloudMonitoring_ListTimeseriesResponse
   */
  public function listTimeseries($project, $metric, $youngest, $optParams = array())
  {
    $params = array('project' => $project, 'metric' => $metric, 'youngest' => $youngest);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_CloudMonitoring_ListTimeseriesResponse");
  }
  /**
   * Put data points to one or more time series for one or more metrics. If a time
   * series does not exist, a new time series will be created. It is not allowed
   * to write a time series point that is older than the existing youngest point
   * of that time series. Points that are older than the existing youngest point
   * of that time series will be discarded silently. Therefore, users should make
   * sure that points of a time series are written sequentially in the order of
   * their end time. (timeseries.write)
   *
   * @param string $project The project ID. The value can be the numeric project
   * ID or string-based project name.
   * @param Google_Service_CloudMonitoring_WriteTimeseriesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudMonitoring_WriteTimeseriesResponse
   */
  public function write($project, Google_Service_CloudMonitoring_WriteTimeseriesRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('write', array($params), "Google_Service_CloudMonitoring_WriteTimeseriesResponse");
  }
}
