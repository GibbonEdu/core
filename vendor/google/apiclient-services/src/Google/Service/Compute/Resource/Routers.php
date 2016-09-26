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
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: field_name comparison_string literal_string.
   *
   * The field_name is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The comparison_string
   * must be either eq (equals) or ne (not equals). The literal_string is the
   * string value to filter to. The literal value must be valid for the type of
   * field you are filtering by (string, number, boolean). For string fields, the
   * literal value is interpreted as a regular expression using RE2 syntax. The
   * literal value must match the entire field.
   *
   * For example, to filter for instances that do not have a name of example-
   * instance, you would use filter=name ne example-instance.
   *
   * You can filter on nested fields. For example, you could filter on instances
   * that have set the scheduling.automaticRestart field to true. Use filtering on
   * nested fields to take advantage of labels to organize and search for results
   * based on label values.
   *
   * To filter on multiple expressions, provide each separate expression within
   * parentheses. For example, (scheduling.automaticRestart eq true) (zone eq us-
   * central1-f). Multiple expressions are treated as AND expressions, meaning
   * that resources must match all expressions to pass the filters.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * maxResults, Compute Engine returns a nextPageToken that can be used to get
   * the next page of results in subsequent list requests.
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
   * @return Google_Service_Compute_Operation
   */
  public function delete($project, $region, $router, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Compute_Operation");
  }
  /**
   * Returns the specified Router resource. Get a list of available routers by
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
   * @opt_param string filter Sets a filter expression for filtering listed
   * resources, in the form filter={expression}. Your {expression} must be in the
   * format: field_name comparison_string literal_string.
   *
   * The field_name is the name of the field you want to compare. Only atomic
   * field types are supported (string, number, boolean). The comparison_string
   * must be either eq (equals) or ne (not equals). The literal_string is the
   * string value to filter to. The literal value must be valid for the type of
   * field you are filtering by (string, number, boolean). For string fields, the
   * literal value is interpreted as a regular expression using RE2 syntax. The
   * literal value must match the entire field.
   *
   * For example, to filter for instances that do not have a name of example-
   * instance, you would use filter=name ne example-instance.
   *
   * You can filter on nested fields. For example, you could filter on instances
   * that have set the scheduling.automaticRestart field to true. Use filtering on
   * nested fields to take advantage of labels to organize and search for results
   * based on label values.
   *
   * To filter on multiple expressions, provide each separate expression within
   * parentheses. For example, (scheduling.automaticRestart eq true) (zone eq us-
   * central1-f). Multiple expressions are treated as AND expressions, meaning
   * that resources must match all expressions to pass the filters.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * maxResults, Compute Engine returns a nextPageToken that can be used to get
   * the next page of results in subsequent list requests.
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
   * Updates the entire content of the Router resource. This method supports patch
   * semantics. (routers.patch)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to update.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
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
   * Updates the entire content of the Router resource. (routers.update)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region for this request.
   * @param string $router Name of the Router resource to update.
   * @param Google_Service_Compute_Router $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_Operation
   */
  public function update($project, $region, $router, Google_Service_Compute_Router $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'router' => $router, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Compute_Operation");
  }
}
