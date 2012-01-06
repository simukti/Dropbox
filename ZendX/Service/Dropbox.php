<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dropbox
 *
 * @author Sarjono Mukti Aji <me@simukti.net>
 */
class ZendX_Service_Dropbox extends Zend_Rest_Client
{
    const ROOT_DROPBOX = 'dropbox';
    const ROOT_SANDBOX = 'sandbox';
    
    const BASE_URI_API      = 'https://api.dropbox.com/1';
    const BASE_URI_CONTENT  = 'https://api-content.dropbox.com/1';
    const BASE_URI_WWW      = 'https://www.dropbox.com/1';
    
    const ACCESS_TOKEN_PATH  = '/oauth/access_token';
    const AUTHORIZE_PATH     = '/oauth/authorize';
    const REQUEST_TOKEN_PATH = '/oauth/request_token';
    
    const ACCOUNT_INFO_PATH      = '/account/info';
    const FILES_MEDIA_PATH       = '/media';
    const FILES_METADATA_PATH    = '/metadata';
    const FILES_PATH             = '/files';
    const FILES_PUT_PATH         = '/files_put';
    const FILES_RESTORE_PATH     = '/restore';
    const FILES_REVISIONS_PATH   = '/revisions';
    const FILES_SEARCH_PATH      = '/search';
    const FILES_SHARES_PATH      = '/shares';
    const FILES_THUMBNAILS_PATH  = '/thumbnails';
    
    const FILEOPS_COPY_PATH          = '/fileops/copy';
    const FILEOPS_CREATE_FOLDER_PATH = '/fileops/create_folder';
    const FILEOPS_DELETE_PATH        = '/fileops/delete';
    const FILEOPS_MOVE_PATH          = '/fileops/move';
    
    /**
     * Dropbox filesize limit = 150MB (150 * 1024 * 1024)
     */
    const DROPBOX_SIZE_LIMIT = 157286400;
    
    /**
     * @var Zend_Oauth_Client
     */
    protected $_localHttpClient = null;
    
    /**
     * @var string
     */
    protected $_dropboxRoot = null;
    
    /**
     * @var Zend_Http_CookieJar
     */
    protected $_cookieJar = null;
    
    /**
     * Zend_Oauth Consumer
     *
     * @var Zend_Oauth_Consumer
     */
    protected $_oauthConsumer = null;
    
    /**
     * Options passed to constructor
     *
     * @var array
     */
    protected $_options = array();
    
    /**
     * @param array|Zend_Config $options {@see Zend_Oauth_Config::setOptions()}
     * @param Zend_Oauth_Consumer $consumer 
     */
    public function __construct($options = null, Zend_Oauth_Consumer $consumer = null)
    {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if (! is_array($options)) {
            $options = array();
        }
        
        if(isset($options['root'])) {
            $this->setDropboxRoot($options['root']);
            unset($options['root']);
        } else {
            $this->setDropboxRoot(self::ROOT_DROPBOX);
        }
        
        $this->_options = $options;
        
        if (isset($options['accessToken']) && $options['accessToken'] instanceof Zend_Oauth_Token_Access) {
            $this->setLocalHttpClient($options['accessToken']->getHttpClient($options));
        } else {
            $this->setLocalHttpClient(clone self::getHttpClient());
            if ($consumer === null) {
                $options['accessTokenUrl']  = self::BASE_URI_API . self::ACCESS_TOKEN_PATH;
                $options['requestTokenUrl'] = self::BASE_URI_API . self::REQUEST_TOKEN_PATH;
                $options['authorizeUrl']    = self::BASE_URI_WWW . self::AUTHORIZE_PATH;
                $this->_oauthConsumer = new Zend_Oauth_Consumer($options);
            } else {
                $this->_oauthConsumer = $consumer;
            }
        }
    }
    
    /**
     * Dropbox root must be 'dropbox' or 'sandbox'
     * 
     * @param string $dropboxRoot
     * @throws Zend_Rest_Exception when $dropboxRoot is not 'dropbox' or 'sandbox'
     */
    public function setDropboxRoot($dropboxRoot)
    {
        if($dropboxRoot !== self::ROOT_DROPBOX && $dropboxRoot !== self::ROOT_SANDBOX) {
            throw new Zend_Rest_Exception(sprintf("Dropbox root name '%s' is not valid. 
                                                   Valid name is '%s' or '%s'", 
                                                   $dropboxRoot, self::ROOT_DROPBOX, self::ROOT_SANDBOX));
        }
        
        $this->_dropboxRoot = $dropboxRoot;
    }
    
    /**
     * @return string Dropbox root folder
     */
    public function getDropboxRoot()
    {
        return $this->_dropboxRoot;
    }
    
    /**
     * Set local HTTP client as distinct from the static HTTP client
     * as inherited from Zend_Rest_Client.
     *
     * @param Zend_Http_Client $client
     * @return ZendX_Service_Dropbox
     */
    public function setLocalHttpClient(Zend_Http_Client $client)
    {
        $this->_localHttpClient = $client;
        $this->_localHttpClient->setHeaders('Accept-Charset', 'ISO-8859-1,utf-8');
        return $this;
    }

    /**
     * Get the local HTTP client as distinct from the static HTTP client
     * inherited from Zend_Rest_Client
     *
     * @return Zend_Oauth_Client
     */
    public function getLocalHttpClient()
    {
        return $this->_localHttpClient;
    }
    
    /**
     * Checks for an authorised state
     *
     * @return boolean
     */
    public function isAuthorised()
    {
        if ($this->_localHttpClient instanceof Zend_Oauth_Client) {
            return true;
        }
        return false;
    }
    
    /**
     * @return string Http response body (json)
     * @link https://www.dropbox.com/developers/reference/api#account-info
     */
    public function accountInfo() 
    {   
        $this->_init();
        
        $uri      = self::BASE_URI_API . self::ACCOUNT_INFO_PATH;
        $request  = $this->_get($uri);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @param string $rev
     * @return Zend_Http_Response
     * @link https://www.dropbox.com/developers/reference/api#files-GET
     */
    public function fileGet($path, $rev = null) 
    {
        $this->_init();
        
        $uri = self::BASE_URI_CONTENT . self::FILES_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $params = array(
            'rev'  => (is_string($rev)) ? $rev : null
        );
        
        $request  = $this->_get($uri, $params);
        
        return $request;
    }
    
    /**
     * @param string $file File absolute path
     * @param string $path
     * @param array $params
     * @return string json results
     * @link https://www.dropbox.com/developers/reference/api#files_put
     */
    public function filePut($file, $path = '', $overwrite = false) 
    {
        $this->_init();
        $file = str_replace('\\', '/', $file);
        if(filesize($file) < self::DROPBOX_SIZE_LIMIT && is_readable($file)) {
            $uri = self::BASE_URI_CONTENT . self::FILES_PUT_PATH  . '/' . $this->getDropboxRoot() . $this->_preparePath($path)
                   . '/' .basename($file);
            
            $data = @fopen($file, 'r');
            
            $params = array(
                'overwrite' => (string)$overwrite,
            );
            
            $request = $this->getLocalHttpClient()
                            ->setUri($uri)
                            ->setRawData($data, mime_content_type($file))
                            // param=val - The URL-encoded parameters for this request. 
                            // They cannot be sent in the request body.
                            ->setParameterGet($params)
                            ->request(Zend_Oauth_Client::PUT);
            
            return $request->getBody();
        } else {
            throw new Zend_Oauth_Exception(sprintf("Filesize '%d' bytes for '%s' is too large", filesize($file), basename($file)));
        }
    }
    
    /**
     * @param string $path
     * @param integer $limit
     * @param string $hash
     * @param boolean $list
     * @param boolean $include_deleted
     * @param string $rev
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#metadata
     */
    public function fileMetadata($path, $limit = 10000, $hash = null, $list = true, $include_deleted = false, $rev = null)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_METADATA_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        /**
         * Params settings from {@link https://github.com/BenTheDesigner/Dropbox/blob/master/Dropbox/API.php#L137-143}
         * @author Ben Tadiar <ben@handcraftedbyben.co.uk>
         * @link https://github.com/benthedesigner/dropbox
         */
        $params = array(
            'file_limit'        => ($limit < 1) ? 1 : (($limit > 10000) ? 10000 : (int) $limit),
            'hash'              => (is_string($hash)) ? $hash : 0,
            'list'              => (int)$list,
            'include_deleted'   => (int)$include_deleted,
            'rev'               => (is_string($rev)) ? $rev : null,
        );
        
        $request = $this->_get($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @param integer $rev_limit
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#revisions
     */
    public function fileRevision($path, $rev_limit = 25) 
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_REVISIONS_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $params = array(
            'rev_limit'  => ($rev_limit < 1) ? 1 : (($rev_limit > 1000) ? 1000 : (int) $rev_limit),
        );
        
        $request = $this->_get($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @param string $rev
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#restore
     */
    public function fileRestore($path, $rev)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_RESTORE_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $params = array(
            'rev' => $rev
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @param string $query
     * @param integer $file_limit
     * @param boolean $include_deleted
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#search
     */
    public function fileSearch($path, $query, $file_limit = 25, $include_deleted = false)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_SEARCH_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $params = array(
            'query'           => (string)$query,
            'file_limit'      => ($file_limit < 1) ? 1 : (($file_limit > 1000) ? 1000 : (int) $file_limit),
            'include_deleted' => (int)$include_deleted
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#shares
     */
    public function fileShares($path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_SHARES_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $request = $this->_post($uri);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#media
     */
    public function fileMedia($path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILES_MEDIA_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $request = $this->_post($uri);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @param string $format
     * @param string $size
     * @return Zend_Http_Response
     * @link https://www.dropbox.com/developers/reference/api#thumbnails
     */
    public function fileThumbnails($path, $format = 'jpeg', $size = 'small')
    {
        $this->_init();
        
        $formatOptions = array( 'jpeg', 'png' );
        $sizeOptions   = array( 'small', 'medium', 'large', 's', 'm', 'l', 'xl');
        
        if(! in_array(strtolower($format), $formatOptions) || ! in_array(strtolower($size), $sizeOptions)) {
            throw new Zend_Oauth_Exception(sprintf("Invalid parameter value format '%s' or size '%s'", $format, $size));
        }
        
        $uri = self::BASE_URI_CONTENT . self::FILES_THUMBNAILS_PATH . '/' . $this->getDropboxRoot() . $this->_preparePath($path);
        
        $params = array(
            'format' => $format,
            'size'   => $size
        );
        
        $request = $this->_get($uri, $params);
        
        return $request;
    }
    
    /**
     * @param string $from_path
     * @param string $to_path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#fileops-copy
     */
    public function fileOpsCopy($from_path, $to_path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILEOPS_COPY_PATH;
        
        $params = array(
            'root'      => $this->getDropboxRoot(),
            'from_path' => $this->_preparePath($from_path),
            'to_path'   => $this->_preparePath($to_path),
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#fileops-create-folder
     */
    public function fileOpsCreateFolder($path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILEOPS_CREATE_FOLDER_PATH;
        
        $params = array(
            'root' => $this->getDropboxRoot(),
            'path' => $this->_preparePath($path)
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#fileops-delete
     */
    public function fileOpsDelete($path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILEOPS_DELETE_PATH;
        
        $params = array(
            'root' => $this->getDropboxRoot(),
            'path' => $this->_preparePath($path)
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * @param string $from_path
     * @param string $to_path
     * @return string json
     * @link https://www.dropbox.com/developers/reference/api#fileops-move
     */
    public function fileOpsMove($from_path, $to_path)
    {
        $this->_init();
        
        $uri = self::BASE_URI_API . self::FILEOPS_MOVE_PATH;
        
        $params = array(
            'root'      => $this->getDropboxRoot(),
            'from_path' => $this->_preparePath($from_path),
            'to_path'   => $this->_preparePath($to_path),
        );
        
        $request = $this->_post($uri, $params);
        
        return $request->getBody();
    }
    
    /**
     * Initialize HTTP authentication
     *
     * @return void
     */
    protected function _init()
    {
        if (! $this->isAuthorised()) {
            throw new Zend_Oauth_Exception('Unauthorized Oauth Request.');
        }
        
        $client = $this->getLocalHttpClient();
        $client->resetParameters();
        
        if (null == $this->_cookieJar) {
            $client->setCookieJar();
            $this->_cookieJar = $client->getCookieJar();
        } else {
            $client->setCookieJar($this->_cookieJar);
        }
    }
    
    /**
     * Method overloading
     *
     * @param  string $method
     * @param  array $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        if (method_exists($this->_oauthConsumer, $method)) {
            $return = call_user_func_array(array($this->_oauthConsumer, $method), $params);
            if ($return instanceof Zend_Oauth_Token_Access) {
                $this->setLocalHttpClient($return->getHttpClient($this->_options));
            }
            return $return;
        }
    }
    
    /**
     * Eliminate relative path and double slash
     * 
     * @example <pre><code>
     * /Public/Photo////Secret.@kadal-kancrit_/../../../pict.jpg
     * to 
     * /Public/Photo/Secret.@kadal-kancrit_/pict.jpg
     * </code></pre>
     * @param string $path
     * @return string
     */
    protected function _preparePath($path)
    {
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }
        
        $path = preg_replace(array("([\.]+[!\w+\.][^\.\w+])", "([\/]+)"), '/', $path);
        
        return $path;
    }
    
    /**
     * @param string $uri
     * @param array $params
     * @return Zend_Http_Response
     */
    protected function _get($uri, $params = array())
    {
        $get = $this->getLocalHttpClient()
                    ->setUri($uri)
                    ->setParameterGet($params);
        
        return $get->request(Zend_Oauth_Client::GET);
    }
    
    /**
     * @param string $uri
     * @param array $params
     * @return Zend_Http_Response
     */
    protected function _post($uri, $params = array())
    {
        $post = $this->getLocalHttpClient()
                     ->setUri($uri)
                     ->setParameterPost($params);
        
        return $post->request(Zend_Oauth_Client::POST);
    }
}
