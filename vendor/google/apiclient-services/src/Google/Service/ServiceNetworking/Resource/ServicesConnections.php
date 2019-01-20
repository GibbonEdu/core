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
   * To connect service to a VPC network peering connection must be established
   * prior to service provisioning. This method must be invoked by the consumer
   * VPC network administrator It will establish a permanent peering connection
   * with a shared network created in the service producer organization and
   * register a allocated IP range(s) to be used for service subnetwork
   * provisioning. This connection will be used for all supported services in the
   * service producer organization, so it only needs to be invoked once.
   * Operation. (connections.create)
   *
   * @param string $parent Provider peering service that is managing peering
   * connectivity for a service provider organization. For Google services that
   * support this functionality it is 'services/servicenetworking.googleapis.com'.
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
   * Service consumers use this method to list configured peering connection for
   * the given service and consumer network. (connections.listServicesConnections)
   *
   * @param string $parent Provider peering service that is managing peering
   * connectivity for a service provider organization. For Google services that
   * support this functionality it is 'services/servicenetworking.googleapis.com'.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string network Network name in the consumer project.   This
   * network must have been already peered with a shared VPC network using
   * CreateConnection method. Must be in a form
   * 'projects/{project}/global/networks/{network}'. {project} is a project
   * number, as in '12345' {network} is network name.
   * @return Google_Service_ServiceNetworking_ListConnectionsResponse
   */
  public function listServicesConnections($parent, $optParams = array())
  {
    $params = array('parent' => $parent);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_ServiceNetworking_ListConnectionsResponse");
  }
}
