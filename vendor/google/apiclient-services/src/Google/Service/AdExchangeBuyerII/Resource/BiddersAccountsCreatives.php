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
 * The "creatives" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyer2Service = new Google_Service_AdExchangeBuyerII(...);
 *   $creatives = $adexchangebuyer2Service->creatives;
 *  </code>
 */
class Google_Service_AdExchangeBuyerII_Resource_BiddersAccountsCreatives extends Google_Service_Resource
{
  /**
   * Deletes a single creative.
   *
   * A creative is deactivated upon deletion and does not count against active
   * snippet quota. A deleted creative should not be used in bidding (all bids
   * with that creative will be rejected). (creatives.delete)
   *
   * @param string $ownerName Name of the owner (bidder or account) of the
   * creative to be deleted. For example:
   *
   * - For an account-level creative for the buyer account representing bidder
   * 123: `bidders/123/accounts/123`
   *
   * - For an account-level creative for the child seat buyer account 456   whose
   * bidder is 123: `bidders/123/accounts/456`
   * @param string $creativeId The ID of the creative to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyerII_Adexchangebuyer2Empty
   */
  public function delete($ownerName, $creativeId, $optParams = array())
  {
    $params = array('ownerName' => $ownerName, 'creativeId' => $creativeId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AdExchangeBuyerII_Adexchangebuyer2Empty");
  }
}
