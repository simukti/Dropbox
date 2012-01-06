This is a Dropbox ReST API client for Zend Framework v1.10+ (current proposal for Zend_Service_Dropbox is not maintained).

##REQUIREMENTS##
- Zend Framework v1.10+
- PHP 5.2.10+

##USAGE##
Dropbox ReST API is using OAuth v1, so I use Zend_Oauth_* for all API request (inspired by Zend_Service_Twitter)

###Initial###
```php
<?php
$options = array(
    // https://api.dropbox.com/developers/apps
    'consumerKey'    => 'CONSUMER_KEY',
    'consumerSecret' => 'CONSUMER_SECRET',
    'callbackUrl'    => 'http://127.0.0.1/dropbox/callback',
);
$dropbox = new ZendX_Service_Dropbox($options);
```

###Login/Authorize###
```php
<?php
$requestToken = $dropbox->getRequestToken();
$dropbox->redirect();
```

###Callback/Get Access Token###
$accessToken need to be inserted to $options['accessToken'] for further API request.

```php
<?php 
// via Controller
$query = $this->getRequest()->getQuery();
$accessToken = $dropbox->getAccessToken($query, $requestToken);

// Save $accessToken to Session or Zend_Config for further request
// Or you can set $accessToken->getToken() and $accessToken->getTokenSecret() 
// to Zend_Oauth_Consumer constructor options
```

###API Call###
```php
<?php
// https://www.dropbox.com/developers/reference/api#account-info
$dropbox->accountInfo(); // return json

// https://www.dropbox.com/developers/reference/api#files-GET
$dropbox->fileGet($path, $rev);

// https://www.dropbox.com/developers/reference/api#files_put
$dropbox->filePut($file, $path, $overwrite);

// https://www.dropbox.com/developers/reference/api#metadata
$dropbox->fileMetadata($path, $limit, $hash, $list, $include_deleted, $rev);

// https://www.dropbox.com/developers/reference/api#revisions
$dropbox->fileRevision($path, $rev_limit);

// https://www.dropbox.com/developers/reference/api#restore
$dropbox->fileRestore($path, $rev);

// https://www.dropbox.com/developers/reference/api#search
$dropbox->fileSearch($path, $query, $file_limit, $include_deleted);

// https://www.dropbox.com/developers/reference/api#shares
$dropbox->fileShares($path);

// https://www.dropbox.com/developers/reference/api#media
$dropbox->fileMedia($path);

// https://www.dropbox.com/developers/reference/api#thumbnails
$dropbox->fileThumbnails($path, $format, $size);

// https://www.dropbox.com/developers/reference/api#fileops-copy
$dropbox->fileOpsCopy($from_path, $to_path);

// https://www.dropbox.com/developers/reference/api#fileops-create-folder
$dropbox->fileOpsCreateFolder($path);

// https://www.dropbox.com/developers/reference/api#fileops-delete
$dropbox->fileOpsDelete($path);

// https://www.dropbox.com/developers/reference/api#fileops-move
$dropbox->fileOpsMove($from_path, $to_path);
```

All api call will return json string, except fileGet() and fileThumbnails() will return original http response from dropbox.

##LICENSE##
All files are licensed under the MIT License.
