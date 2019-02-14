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
 * The "services" collection of methods.
 * Typical usage is:
 *  <code>
 *   $servicenetworkingService = new Google_Service_ServiceNetworking(...);
 *   $services = $servicenetworkingService->services;
 *  </code>
 */
class Google_Service_ServiceNetworking_Resource_Services extends Google_Service_Resource
{
  /**
   * For service producers, provisions a new subnet in a peered service's shared
   * VPC network in the requested region and with the requested size that's
   * expressed as a CIDR range (number of leading bits of ipV4 network mask). The
   * method checks against the assigned allocated ranges to find a non-conflicting
   * IP address range. The method will reuse a subnet if subsequent calls contain
   * the same subnet name, region, and prefix length. This method will make
   * producer's tenant project to be a shared VPC service project as needed. The
   * response from the `get` operation will be of type `Subnetwork` if the
   * operation successfully completes. (services.addSubnetwork)
   *
   * @param string $parent Required. A tenant project in the service producer
   * organization, in the following format: services/{service}/{collection-id
   * }/{resource-id}. {collection-id} is the cloud resource collection type that
   * represents the tenant project. Only `projects` are supported. {resource-id}
   * is the tenant project numeric id, such as `123456`. {service} the name of the
   * peering service, such as `service-peering.example.com`. This service must
   * already be enabled in the service consumer's project.
   * @param Google_Service_ServiceNetworking_AddSubnetworkRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ServiceNetworking_Operation
   */
  public function addSubnetwork($parent, Google_Service_ServiceNetworking_AddSubnetworkRequest $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('addSubnetwork', array($params), "Google_Service_ServiceNetworking_Operation");
  }
  /**
   * Service producers can use this method to find a currently unused range within
   * consumer allocated ranges.   This returned range is not reserved, and not
   * guaranteed to remain unused. It will validate previously provided allocated
   * ranges, find non-conflicting sub-range of requested size (expressed in number
   * of leading bits of ipv4 network mask, as in CIDR range notation). Operation
   * (services.searchRange)
   *
   * @param string $parent Required. This is in a form services/{service}.
   * {service} the name of the private access management service, for example
   * 'service-peering.example.com'.
   * @param Google_Service_ServiceNetworking_SearchRangeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ServiceNetworking_Operation
   */
  public function searchRange($parent, Google_Service_ServiceNetworking_SearchRangeRequest $postBody, $optParams = array())
  {
    $params = array('parent' => $parent, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('searchRange', array($params), "Google_Service_ServiceNetworking_Operation");
  }
  /**
   * Updates the allocated ranges that are assigned to a connection. The response
   * from the `get` operation will be of type `Connection` if the operation
   * successfully completes. (services.updateConnections)
   *
   * @param string $name The service producer peering service that is managing
   * peering connectivity for a service producer organization. For Google services
   * that support this functionality, this is
   * `services/servicenetworking.googleapis.com`.
   * @param Google_Service_ServiceNetworking_Connection $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask The update mask. If this is omitted, it defaults
   * to "*". You can only update the listed peering ranges.
   * @opt_param bool force If a previously defined allocated range is removed,
   * force flag must be set to true.
   * @return Google_Service_ServiceNetworking_Operation
   */
  public function updateConnections($name, Google_Service_ServiceNetworking_Connection $postBody, $optParams = array())
  {
    $params = array('name' => $name, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('updateConnections', array($params), "Google_Service_ServiceNetworking_Operation");
  }
}
