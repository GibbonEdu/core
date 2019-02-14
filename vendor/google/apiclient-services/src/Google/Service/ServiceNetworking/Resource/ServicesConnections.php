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
 * The "connections" collection of methods.
 * Typical usage is:
 *  <code>
 *   $servicenetworkingService = new Google_Service_ServiceNetworking(...);
 *   $connections = $servicenetworkingService->connections;
 *  </code>
 */
class Google_Service_ServiceNetworking_Resource_ServicesConnections extends Google_Service_Resource
{
  /**
   * Creates a private connection that establishes a VPC Network Peering
   * connection to a VPC network in the service producer's organization. The
   * administrator of the service consumer's VPC network invokes this method. The
   * administrator must assign one or more allocated IP ranges for provisioning
   * subnetworks in the service producer's VPC network. This connection is used
   * for all supported services in the service producer's organization, so it only
   * needs to be invoked once. The response from the `get` operation will be of
   * type `Connection` if the operation successfully completes.
   * (connections.create)
   *
   * @param string $parent The service that is managing peering connectivity for a
   * service producer's organization. For Google services that support this
   * functionality, this value is `services/servicenetworking.googleapis.com`.
   * @param Google_Service_ServiceNetworking_Connection $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ServiceNetworking_Operation
   */
  public function create($parent, Google_Service_ServiceNetworking_Connection $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_ServiceNetworking_Operation");
  }
  /**
   * List the private connections that are configured in a service consumer's VPC
   * network. (connections.listServicesConnections)
   *
   * @param string $parent The service that is managing peering connectivity for a
   * service producer's organization. For Google services that support this
   * functionality, this value is `services/servicenetworking.googleapis.com`. If
   * you specify `-` as the parameter value, all configured public peering
   * services are listed.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string network The name of service consumer's VPC network that's
   * connected with service producer network through a private connection. The
   * network name must be in the following format:
   * `projects/{project}/global/networks/{network}`. {project} is a project
   * number, such as in `12345` that includes the VPC service consumer's VPC
   * network. {network} is the name of the service consumer's VPC network.
   * @return Google_Service_ServiceNetworking_ListConnectionsResponse
   */
  public function listServicesConnections($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_ServiceNetworking_ListConnectionsResponse");
  }
}
