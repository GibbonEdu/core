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
 * The "regionInstanceGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeService = new Google_Service_Compute(...);
 *   $regionInstanceGroups = $computeService->regionInstanceGroups;
 *  </code>
 */
class Google_Service_Compute_Resource_RegionInstanceGroups extends Google_Service_Resource
{
  /**
   * Returns the specified instance group resource. (regionInstanceGroups.get)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroup Name of the instance group resource to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Compute_InstanceGroup
   */
  public function get($project, $region, $instanceGroup, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'instanceGroup' => $instanceGroup);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Compute_InstanceGroup");
  }
  /**
   * Retrieves the list of instance group resources contained within the specified
   * region. (regionInstanceGroups.listRegionInstanceGroups)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
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
   * @return Google_Service_Compute_RegionInstanceGroupList
   */
  public function listRegionInstanceGroups($project, $region, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Compute_RegionInstanceGroupList");
  }
  /**
   * Lists the instances in the specified instance group and displays information
   * about the named ports. Depending on the specified options, this method can
   * list all instances or only the instances that are running.
   * (regionInstanceGroups.listInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroup Name of the regional instance group for which we
   * want to list the instances.
   * @param Google_Service_Compute_RegionInstanceGroupsListInstancesRequest $postBody
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
   * @return Google_Service_Compute_RegionInstanceGroupsListInstances
   */
  public function listInstances($project, $region, $instanceGroup, Google_Service_Compute_RegionInstanceGroupsListInstancesRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'instanceGroup' => $instanceGroup, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('listInstances', array($params), "Google_Service_Compute_RegionInstanceGroupsListInstances");
  }
  /**
   * Sets the named ports for the specified regional instance group.
   * (regionInstanceGroups.setNamedPorts)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroup The name of the regional instance group where
   * the named ports are updated.
   * @param Google_Service_Compute_RegionInstanceGroupsSetNamedPortsRequest $postBody
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
  public function setNamedPorts($project, $region, $instanceGroup, Google_Service_Compute_RegionInstanceGroupsSetNamedPortsRequest $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'region' => $region, 'instanceGroup' => $instanceGroup, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('setNamedPorts', array($params), "Google_Service_Compute_Operation");
  }
}
