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
   * Service producers use this method to provision a new subnet in peered service
   * shared VPC network. It will validate previously provided allocated ranges,
   * find non-conflicting sub-range of requested size (expressed in number of
   * leading bits of ipv4 network mask, as in CIDR range notation). It will then
   * create a subnetwork in the request region. The subsequent call will try to
   * reuse the subnetwork previously created if subnetwork name, region and prefix
   * length of the IP range match. Operation (services.addSubnetwork)
   *
   * @param string $parent Required. This is a 'tenant' project in the service
   * producer organization. services/{service}/{collection-id}/{resource-id}
   * {collection id} is the cloud resource collection type representing the tenant
   * project. Only 'projects' are currently supported. {resource id} is the tenant
   * project numeric id: '123456'. {service} the name of the peering service, for
   * example 'service-peering.example.com'. This service must be activated. in the
   * consumer project.
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
}
