This is a Dropbox ReST API client for Zend Framework v1.10+ (current proposal for Zend_Service_Dropbox is not maintained).

##REQUIREMENTS##
- Zend Framework v1.10+
- PHP 5.2.10+

##USAGE##
Dropbox ReST API is using OAuth v1, so I use Zend_Oauth_* for all API request (inspired by Zend_Service_Twitter)

####Initial####
`
<?php
//...........

$options = array(
    'consumerKey'    => 'CONSUMER_KEY',
    'consumerSecret' => 'CONSUMER_SECRET',
);
$dropbox = new ZendX_Service_Dropbox($options);
`

###Login/Authorize###
`
<?php
//...........

$requestToken = $dropbox->getRequestToken();
$dropbox->redirect();
`

###Callback/Get Access Token###
$accessToken need to be inserted to $options['accessToken'] for further API request.

`
<?php 
//...........

// via Controller
$query = $this->getRequest()->getQuery();
$accessToken = $dropbox->getAccessToken($query, $requestToken);

// Save $accessToken to Session or Zend_Config for further request
// Or you can set $accessToken->getToken() and $accessToken->getTokenSecret() 
// to Zend_Oauth_Consumer constructor options
`

For more example, please see *DropboxController.php*


##LICENSE##
This source file is subject to the new BSD license that is bundled
with this package in the file LICENSE.txt.
It is also available through the world-wide-web at this URL:
http://framework.zend.com/license/new-bsd
If you did not receive a copy of the license and are unable to
obtain it through the world-wide-web, please send an email
to license@zend.com so we can send you a copy immediately.
