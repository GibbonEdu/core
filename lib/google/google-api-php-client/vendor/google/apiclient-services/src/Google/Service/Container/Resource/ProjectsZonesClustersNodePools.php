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
 * The "nodePools" collection of methods.
 * Typical usage is:
 *  <code>
 *   $containerService = new Google_Service_Container(...);
 *   $nodePools = $containerService->nodePools;
 *  </code>
 */
class Google_Service_Container_Resource_ProjectsZonesClustersNodePools extends Google_Service_Resource
{
  /**
   * Creates a node pool for a cluster. (nodePools.create)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster.
   * @param Google_Service_Container_CreateNodePoolRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function create($projectId, $zone, $clusterId, Google_Service_Container_CreateNodePoolRequest $postBody, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Container_Operation");
  }
  /**
   * Deletes a node pool from a cluster. (nodePools.delete)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster.
   * @param string $nodePoolId The name of the node pool to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_Operation
   */
  public function delete($projectId, $zone, $clusterId, $nodePoolId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId, 'nodePoolId' => $nodePoolId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Container_Operation");
  }
  /**
   * Retrieves the node pool requested. (nodePools.get)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster.
   * @param string $nodePoolId The name of the node pool.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_NodePool
   */
  public function get($projectId, $zone, $clusterId, $nodePoolId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId, 'nodePoolId' => $nodePoolId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Container_NodePool");
  }
  /**
   * Lists the node pools for a cluster.
   * (nodePools.listProjectsZonesClustersNodePools)
   *
   * @param string $projectId The Google Developers Console [project ID or project
   * number](https://developers.google.com/console/help/new/#projectnumber).
   * @param string $zone The name of the Google Compute Engine
   * [zone](/compute/docs/zones#available) in which the cluster resides.
   * @param string $clusterId The name of the cluster.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Container_ListNodePoolsResponse
   */
  public function listProjectsZonesClustersNodePools($projectId, $zone, $clusterId, $optParams = array())
  {
    $params = array('projectId' => $projectId, 'zone' => $zone, 'clusterId' => $clusterId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Container_ListNodePoolsResponse");
  }
}
