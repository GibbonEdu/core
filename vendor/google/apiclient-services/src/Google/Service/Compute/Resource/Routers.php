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
 * The "routers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeService = new Google_Service_Compute(...);
 *   $routers = $computeService->routers;
 *  </code>
 */
class Google_Service_Compute_Resource_Routers extends Google_Service_Resource
{
  /**
   * Retrieves an aggregated list of routers. (routers.aggregatedList)
   *
   * @param string $project Project ID for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. The expression must specify the field name, a comparison
   * operator, and the value that you want to use for filtering. The value must be
   * a string, a number, or a boolean. The comparison operator must be either =,
   * !=, >, or <.
   *
   * For example, if you are filtering Compute Engine instances, you can exclude
   * instances named example-instance by specifying name != example-instance.
   *
   * You can also filter nested fields. For example, you could specify
   * scheduling.automaticRestart = false to include instances only if they are not
   * scheduled for automatic restarts. You can use filtering on nested fields to
   * filter based on resource labels.
   *
   * To filter on multiple expressions, provide each separate expression within
   * parentheses. For example, (scheduling.automaticRestart = true) (cpuPlatform =
   * "Intel Skylake"). By default, each expression is an AND expression. However,
   * you can include AND and OR expressions explicitly. For example, (cpuPlatform
   * = "Intel Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true).
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * maxResults, Compute Engine returns a nextPageToken that can be used to get
   * the next page of results in subsequent list requests. Acceptable values are 0
   * to 500, inclusive. (Default: 500)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name.
   *
   * You can also sort results in descending order based on the creation timestamp
   * using orderBy="creationTimestamp desc". This sorts results based on the
   * creationTimestamp field in reverse chronological order (newest result first).
   * Use this to sort resources like operations so that the newest operation is
   * returned first.
   *
   * Currently, only sorting by name or creationTimestamp desc is supported.
   * @opt_param string pageToken Specifies a page token to use. Set pageToken to
   * the nextPageToken returned by a previous list request to get the next page of
   * results.
   * @return Google_Service_Compute_RouterAggregatedList
   */
  public function aggregatedList($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('aggregatedList', array($params), "Google_Service_Compute_RouterAggregatedList");
  }
  /**
   * Deletes the specified Router resource. (routers.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to delete.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed.
   *
   * For example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments.
   *
   * The request ID must be a valid UUID with the exception that zero UUID is not
   * supported (00000000-0000-0000-0000-000000000000).
   * @return Google_Service_Compute_Operation
   */
  public function delete($project, $region, $router, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Returns the specified Router resource. Gets a list of available routers by
   * making a list() request. (routers.get)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Router
   */
  public function get($project, $region, $router, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Compute_Router");
  }
  /**
   * Retrieves runtime information of the specified router.
   * (routers.getRouterStatus)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to query.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_RouterStatusResponse
   */
  public function getRouterStatus($project, $region, $router, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router);
    $params = array_merge($params, $optParams);
    return $this->call('getRouterStatus', array($params), "Google_Service_Compute_RouterStatusResponse");
  }
  /**
   * Creates a Router resource in the specified project and region using the data
   * included in the request. (routers.insert)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed.
   *
   * For example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments.
   *
   * The request ID must be a valid UUID with the exception that zero UUID is not
   * supported (00000000-0000-0000-0000-000000000000).
   * @return Google_Service_Compute_Operation
   */
  public function insert($project, $region, Google_Service_Compute_Router $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Retrieves a list of Router resources available to the specified project.
   * (routers.listRouters)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. The expression must specify the field name, a comparison
   * operator, and the value that you want to use for filtering. The value must be
   * a string, a number, or a boolean. The comparison operator must be either =,
   * !=, >, or <.
   *
   * For example, if you are filtering Compute Engine instances, you can exclude
   * instances named example-instance by specifying name != example-instance.
   *
   * You can also filter nested fields. For example, you could specify
   * scheduling.automaticRestart = false to include instances only if they are not
   * scheduled for automatic restarts. You can use filtering on nested fields to
   * filter based on resource labels.
   *
   * To filter on multiple expressions, provide each separate expression within
   * parentheses. For example, (scheduling.automaticRestart = true) (cpuPlatform =
   * "Intel Skylake"). By default, each expression is an AND expression. However,
   * you can include AND and OR expressions explicitly. For example, (cpuPlatform
   * = "Intel Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true).
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * maxResults, Compute Engine returns a nextPageToken that can be used to get
   * the next page of results in subsequent list requests. Acceptable values are 0
   * to 500, inclusive. (Default: 500)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name.
   *
   * You can also sort results in descending order based on the creation timestamp
   * using orderBy="creationTimestamp desc". This sorts results based on the
   * creationTimestamp field in reverse chronological order (newest result first).
   * Use this to sort resources like operations so that the newest operation is
   * returned first.
   *
   * Currently, only sorting by name or creationTimestamp desc is supported.
   * @opt_param string pageToken Specifies a page token to use. Set pageToken to
   * the nextPageToken returned by a previous list request to get the next page of
   * results.
   * @return Google_Service_Compute_RouterList
   */
  public function listRouters($project, $region, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Compute_RouterList");
  }
  /**
   * Patches the specified Router resource with the data included in the request.
   * This method supports PATCH semantics and uses JSON merge patch format and
   * processing rules. (routers.patch)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to patch.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed.
   *
   * For example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments.
   *
   * The request ID must be a valid UUID with the exception that zero UUID is not
   * supported (00000000-0000-0000-0000-000000000000).
   * @return Google_Service_Compute_Operation
   */
  public function patch($project, $region, $router, Google_Service_Compute_Router $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Preview fields auto-generated during router create and update operations.
   * Calling this method does NOT create or update the router. (routers.preview)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to query.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_RoutersPreviewResponse
   */
  public function preview($project, $region, $router, Google_Service_Compute_Router $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('preview', array($params), "Google_Service_Compute_RoutersPreviewResponse");
  }
  /**
   * Updates the specified Router resource with the data included in the request.
   * (routers.update)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to update.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed.
   *
   * For example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments.
   *
   * The request ID must be a valid UUID with the exception that zero UUID is not
   * supported (00000000-0000-0000-0000-000000000000).
   * @return Google_Service_Compute_Operation
   */
  public function update($project, $region, $router, Google_Service_Compute_Router $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Compute_Operation");
  }
}
