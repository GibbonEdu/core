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
 * The "groupItems" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $groupItems = $youtubeAnalyticsService->groupItems;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_Resource_GroupItems extends Google_Service_Resource
{
  /**
   * Removes an item from a group. (groupItems.delete)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner This parameter can only be used in a
   * properly authorized request. **Note:** This parameter is intended exclusively
   * for YouTube content partners that own and manage many different YouTube
   * channels.
   *
   * The `onBehalfOfContentOwner` parameter indicates that the request's
   * authorization credentials identify a YouTube user who is acting on behalf of
   * the content owner specified in the parameter value. It allows content owners
   * to authenticate once and get access to all their video and channel data,
   * without having to provide authentication credentials for each individual
   * channel. The account that the user authenticates with must be linked to the
   * specified YouTube content owner.
   * @opt_param string id The `id` parameter specifies the YouTube group item ID
   * of the group item that is being deleted.
   * @return Google_Service_YouTubeAnalytics_EmptyResponse
   */
  public function delete($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_YouTubeAnalytics_EmptyResponse");
  }
  /**
   * Creates a group item. (groupItems.insert)
   *
   * @param Google_Service_YouTubeAnalytics_GroupItem $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner This parameter can only be used in a
   * properly authorized request. **Note:** This parameter is intended exclusively
   * for YouTube content partners that own and manage many different YouTube
   * channels.
   *
   * The `onBehalfOfContentOwner` parameter indicates that the request's
   * authorization credentials identify a YouTube user who is acting on behalf of
   * the content owner specified in the parameter value. It allows content owners
   * to authenticate once and get access to all their video and channel data,
   * without having to provide authentication credentials for each individual
   * channel. The account that the user authenticates with must be linked to the
   * specified YouTube content owner.
   * @return Google_Service_YouTubeAnalytics_GroupItem
   */
  public function insert(Google_Service_YouTubeAnalytics_GroupItem $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTubeAnalytics_GroupItem");
  }
  /**
   * Returns a collection of group items that match the API request parameters.
   * (groupItems.listGroupItems)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner This parameter can only be used in a
   * properly authorized request. **Note:** This parameter is intended exclusively
   * for YouTube content partners that own and manage many different YouTube
   * channels.
   *
   * The `onBehalfOfContentOwner` parameter indicates that the request's
   * authorization credentials identify a YouTube user who is acting on behalf of
   * the content owner specified in the parameter value. It allows content owners
   * to authenticate once and get access to all their video and channel data,
   * without having to provide authentication credentials for each individual
   * channel. The account that the user authenticates with must be linked to the
   * specified YouTube content owner.
   * @opt_param string groupId The `groupId` parameter specifies the unique ID of
   * the group for which you want to retrieve group items.
   * @return Google_Service_YouTubeAnalytics_ListGroupItemsResponse
   */
  public function listGroupItems($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTubeAnalytics_ListGroupItemsResponse");
  }
}
