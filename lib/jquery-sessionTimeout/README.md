# sessionTimeout
[![Build Status](https://api.travis-ci.org/travishorn/jquery-sessionTimeout.png)](https://travis-ci.org/travishorn/jquery-sessionTimeout)

## Description
After a set amount of time, a dialog is shown to the user with the option to either log out now, or stay connected. If log out now is selected, the page is redirected to a logout URL. If stay connected is selected, a keep-alive URL is requested through AJAX. If no options is selected after another set amount of time, the page is automatically redirected to a timeout URL.

## Usage
1. Include jQuery
2. Include jQuery UI (for dialog)
3. Include jquery.sessionTimeout.js
4. Call `$.sessionTimeout();` after document ready

## Options
**message**<br>
Text shown to user in dialog after warning period.
Default: 'Your session is about to expire.'

**keepAliveUrl**<br>
URL to call through AJAX to keep session alive. This resource should do something innocuous that would keep the session alive, which will depend on your server-side platform.<br>
Default: '/keep-alive'

**redirUrl**<br>
URL to take browser to if no action is take after warning period.<br>
Default: '/timed-out'

**logoutUrl**<br>
URL to take browser to if user clicks "Log Out Now".<br>
Default: '/log-out'

**warnAfter**<br>
Time in milliseconds after page is opened until warning dialog is opened.<br>
Default: 900000 (15 minutes)

**redirAfter**<br>
Time in milliseconds after page is opened until browser is redirected to redirUrl.<br>
Default: 1200000 (20 minutes)

## Links
* [Online Demo](http://jsfiddle.net/xHEF9/515/)
* [Screenshots](https://sites.google.com/site/tpopsjqueryplugins/sessiontimeout/screenshots)
