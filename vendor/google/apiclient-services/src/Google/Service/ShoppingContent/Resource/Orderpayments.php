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
 * The "orderpayments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $contentService = new Google_Service_ShoppingContent(...);
 *   $orderpayments = $contentService->orderpayments;
 *  </code>
 */
class Google_Service_ShoppingContent_Resource_Orderpayments extends Google_Service_Resource
{
  /**
   * Notify about successfully authorizing user's payment method for a given
   * amount. (orderpayments.notifyauthapproved)
   *
   * @param string $merchantId The ID of the account that manages the order. This
   * cannot be a multi-client account.
   * @param string $orderId The ID of the order for for which payment
   * authorization is happening.
   * @param Google_Service_ShoppingContent_OrderpaymentsNotifyAuthApprovedRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_OrderpaymentsNotifyAuthApprovedResponse
   */
  public function notifyauthapproved($merchantId, $orderId, Google_Service_ShoppingContent_OrderpaymentsNotifyAuthApprovedRequest $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('notifyauthapproved', array($params), "Google_Service_ShoppingContent_OrderpaymentsNotifyAuthApprovedResponse");
  }
  /**
   * Notify about failure to authorize user's payment method.
   * (orderpayments.notifyauthdeclined)
   *
   * @param string $merchantId The ID of the account that manages the order. This
   * cannot be a multi-client account.
   * @param string $orderId The ID of the order for which payment authorization
   * was declined.
   * @param Google_Service_ShoppingContent_OrderpaymentsNotifyAuthDeclinedRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_OrderpaymentsNotifyAuthDeclinedResponse
   */
  public function notifyauthdeclined($merchantId, $orderId, Google_Service_ShoppingContent_OrderpaymentsNotifyAuthDeclinedRequest $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('notifyauthdeclined', array($params), "Google_Service_ShoppingContent_OrderpaymentsNotifyAuthDeclinedResponse");
  }
  /**
   * Notify about charge on user's selected payments method.
   * (orderpayments.notifycharge)
   *
   * @param string $merchantId The ID of the account that manages the order. This
   * cannot be a multi-client account.
   * @param string $orderId The ID of the order for which charge is happening.
   * @param Google_Service_ShoppingContent_OrderpaymentsNotifyChargeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_OrderpaymentsNotifyChargeResponse
   */
  public function notifycharge($merchantId, $orderId, Google_Service_ShoppingContent_OrderpaymentsNotifyChargeRequest $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('notifycharge', array($params), "Google_Service_ShoppingContent_OrderpaymentsNotifyChargeResponse");
  }
  /**
   * Notify about refund on user's selected payments method.
   * (orderpayments.notifyrefund)
   *
   * @param string $merchantId The ID of the account that manages the order. This
   * cannot be a multi-client account.
   * @param string $orderId The ID of the order for which charge is happening.
   * @param Google_Service_ShoppingContent_OrderpaymentsNotifyRefundRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_ShoppingContent_OrderpaymentsNotifyRefundResponse
   */
  public function notifyrefund($merchantId, $orderId, Google_Service_ShoppingContent_OrderpaymentsNotifyRefundRequest $postBody, $optParams = array())
  {
    $params = array('merchantId' => $merchantId, 'orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('notifyrefund', array($params), "Google_Service_ShoppingContent_OrderpaymentsNotifyRefundResponse");
  }
}
