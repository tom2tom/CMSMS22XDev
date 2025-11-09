<?php
/**
 * cms_http_request class
 *
 * This is a wrapper class that uses either cURL or fsockopen to
 * interact with the www. This class can be used by scripts that
 * need to communicate via various APIs which support REST.
 * cURL version 7.19.7 or higher is required.
 *
 * Adapted from HTTP class <http://www.phpfour.com/lib/http>
 * by Emran Hasan <phpfour@gmail.com>
 *
 * Example use:
 * <pre>
 * $httpConfig = [];
 * $httpConfig['method']     = 'GET';
 * $httpConfig['target']     = 'http://www.somedomain.com/index.html';
 * $httpConfig['referrer']   = 'http://www.somedomain.com';
 * $httpConfig['user_agent'] = 'My Crawler';
 * $httpConfig['timeout']    = 30;
 * $httpConfig['params']     = ['var1' => 'testvalue', 'var2' => 'somevalue'];
 * $http = new cms_http_request($httpConfig);
 * $res = $http->execute();
 * </pre>
 *
 * @package     CMS
 * @license     GPL
 * @author      Md Emran Hasan
 */
class cms_http_request
{
    /**
     * Contains the target URL
     *
     * @var string
     */
    private $target;

    /**
     * socket
     *
     */
    private $_socket;

    /**
     * Contains the target host
     *
     * @var string
     */
    private $host;

    /**
     * Contains the target port
     *
     * @var int
     */
    private $port;

    /**
     * Contains the target path
     *
     * @var string
     */
    private $path;

    /**
     * Contains the target schema
     *
     * @var string
     */
    private $schema;

    /**
     * Contains the http method ('GET', 'POST' or 'HEAD')
     *
     * @var string
     */
    private $method;

    /**
     * Contains raw post data
     *
     * @var string
     */
    private $rawPostData;

    /**
     * Contains the parameters for request
     *
     * @var array
     */
    private $params;

    /**
     * Contains the cookies for request
     *
     * @var array
     */
    private $cookies;

    /**
     * Contains the cookies retrieved from response
     *
     * @var array
     */
    private $_cookies;

    /**
     * Number of seconds to timeout
     *
     * @var int
     */
    private $timeout;

    /**
     * Whether to use cURL or not
     * If TRUE, but a sufficient cURL version is not installed,
     * cURL will not be used.
     *
     * @var bool
     */
    private $useCurl;

    /**
     * Contains the referrer URL
     *
     * @var string
     */
    private $referrer;

    /**
     * Contains the User agent string
     *
     * @var string
     */
    private $userAgent;

    /**
     * Contains the cookie path (to be used with cURL)
     *
     * @var string
     */
    private $cookiePath;

    /**
     * Whether to use cookie at all
     *
     * @var bool
     */
    private $useCookie;

    /**
     * Whether to store cookie for subsequent requests
     *
     * @var bool
     */
    private $saveCookie;

    /**
     * Contains the Username (for authentication)
     *
     * @var string
     */
    private $username;

    /**
     * Contains the Password (for authentication)
     *
     * @var string
     */
    private $password;

    /**
     * Contains the fetched web source
     *
     * @var string
     */
    private $result;

    /**
     * Contains the last headers
     *
     * @var string
     */
    private $headers;

    /**
     * Contains the last call's http status code
     *
     * @var string
     */
    private $status;

    /**
     * Whether to follow http redirect or not
     *
     * @var bool
     */
    private $redirect;

    /**
     * The maximum number of redirect to follow
     *
     * @var integer
     */
    private $maxRedirect;

    /**
     * The current number of redirects
     *
     * @var int
     */
    private $curRedirect;

    /**
     * Contains any error occurred
     *
     * @var string
     */
    private $error;

    /**
     * Store the next token
     *
     * @var string
     */
    private $nextToken;

    /**
     * Whether to keep debug messages
     *
     * @var bool
     */
    private $debug;

    /**
     * Stores optional http headers
     *
     * @var array
     */
    private $headerArray;

    /**
     * Stores the debug messages
     *
     * @var array
     * @todo will keep debug messages
     */
    private $debugMsg;

    /**
     * Stores proxy information (host:port)
     *
     * @var string
     */
    private $proxy;

    /**
     * Constructor
     *
     * @param array $config Optional settings
     */
    public function __construct($config = [])
    {
        if ($config) {
            $this->initialize($config);
        }
        else {
            $this->clear();
        }
    }

    /**
     * Initialize preferences
     *
     * This function will take an associative array of config values and
     * initialize the class variables using them.
     *
     * @param array $config Optional settings
     */
    public function initialize($config = [])
    {
        $this->clear();
        foreach ($config as $key => $val) {
            if (isset($this->$key)) {
                $method = 'set' . ucfirst(str_replace('_', '', $key));

                if (method_exists($this, $method)) {
                    $this->$method($val);
                }
                else {
                    $this->$key = $val;
                }
            }
        }
    }

    /**
     * Clear everything
     *
     * Clears all the properties of the class and sets the object to
     * the beginning state. Handy for doing subsequent requests with
     * different data.
     */
    public function clear()
    {
        // Set the request defaults
        $this->host         = '';
        $this->port         = 0;
        $this->path         = '';
        $this->target       = '';
        $this->method       = 'GET';
        $this->schema       = 'http';
        $this->params       = [];
        $this->headers      = [];
        $this->cookies      = [];
        $this->_cookies     = [];
        $this->headerArray  = [];
        $this->proxy        = null; // mixed bool|int|string|array|resource

        // Set the config details
        $this->debug        = FALSE;
        $this->error        = '';
        $this->status       = 0;
        $this->timeout      = 25;
        $this->useCurl      = TRUE;
        $this->referrer     = CMS_ROOT_URL.'::'.CMS_VERSION;
        $this->username     = '';
        $this->password     = '';
        $this->redirect     = FALSE;
        $this->result       = null; // mixed

        // Set the cookie and agent defaults
        $this->nextToken    = '';
        $this->useCookie    = TRUE;
        $this->saveCookie   = TRUE;
        $this->maxRedirect  = 3;
        $this->cookiePath   = TMP_CACHE_LOCATION.'/c'.md5(session_id().__CLASS__).'.dat'; // by default, use a session-specific cookie file.
        $this->userAgent    = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.9 CMSMS:'.CMS_VERSION;
    }

    /**
     * Clear all cookies
     *
     * @author Robert Campbell
     */
    public function resetCookies()
    {
        if ($this->cookiePath) { @unlink($this->cookiePath); }
    }

    /**
     * Set target URL
     *
     * @param string $url URL of target resource
     */
    public function setTarget($url)
    {
        if ($url) { $this->target = $url; }
    }

    /**
     * Set request method
     *
     * @param string $method HTTP method to use ('GET', 'POST' or 'HEAD')
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
            case 'POST':
            case 'HEAD':
                $this->method = $method;
        }
    }

    /**
     * Set referrer URL
     *
     * @param string $referrer URL of referrer page
     */
    public function setReferrer($referrer)
    {
        if ($referrer) { $this->referrer = $referrer; }
    }

    /**
     * Set user-agent
     *
     * @param string $agent Full user agent string
     */
    public function setUseragent($agent)
    {
        if ($agent) { $this->userAgent = $agent; }
    }

    /**
     * Set timeout of execution
     *
     * @param int $seconds Timeout delay in seconds
     */
    public function setTimeout($seconds)
    {
        if ($seconds > 0) { $this->timeout = (int)$seconds; }
    }

    /**
     * Set cookie path (cURL only)
     *
     * @param string $path File location of cookiejar
     */
    public function setCookiepath($path)
    {
        if ($path) { $this->cookiePath = $path; }
    }

    /**
     * Set post method and data directly
     *
     * @param string $data
     */
    public function setRawPostData($data)
    {
        $this->setMethod('POST');
        $this->rawPostData = $data;
    }

    /**
     * Set request parameters
     *
     * @param mixed array or string $dataArray Request parameter(s)
     */
    public function setParams($dataArray)
    {
        if (is_array($dataArray)) {
            $this->params = array_merge($this->params, $dataArray);
        }
        else {
            $this->setRawPostData($dataArray);
        }
    }

    /**
     * Set basic http authentication realm
     *
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     */
    public function setAuth($username, $password)
    {
        if ($username && $password) {
            $this->username = $username;
            $this->password = $password;
        }
    }

    /**
     * Set maximum number of redirection to follow
     *
     * @param int $value Maximum number of redirects
     */
    public function setMaxredirect($value)
    {
        if ($value) { $this->maxRedirect = $value; }
    }

    /**
     * Add request parameters
     *
     * @param string $name Name of the parameter
     * @param string $value Value of the parameter
     */
    public function addParam($name, $value)
    {
        if ($name && $value) { $this->params[$name] = $value; }
    }

    /**
     * Add a cookie to the request
     *
     * @param string $name Name of cookie
     * @param string $value Value of cookie
     */
    public function addCookie($name, $value)
    {
        if ($name && $value) { $this->cookies[$name] = $value; }
    }

    /**
     * Set whether to use cURL
     *
     * @param bool $value Whether to use cURL or not
     */
    public function useCurl($value = TRUE)
    {
        if (is_bool($value)) { $this->useCurl = $value; }
    }

    /**
     * Set whether to use cookies
     *
     * @param bool $value Whether to use cookies or not
     */
    public function useCookie($value = TRUE)
    {
        if (is_bool($value)) { $this->useCookie = $value; }
    }

    /**
     * Set whether to save persistent cookies in subsequent calls
     *
     * @param bool $value Whether to save persistent cookies or not
     */
    public function saveCookie($value = TRUE)
    {
        if (is_bool($value)) { $this->saveCookie = $value; }
    }

    /**
     * Set whether to follow HTTP redirects
     *
     * @param bool $value Whether to follow HTTP redirects or not
     */
    public function followRedirects($value = TRUE)
    {
        if (is_bool($value)) { $this->redirect = $value; }
    }

    /**
     * Get execution result body
     *
     * @return string output of execution
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get execution result headers
     *
     * @return array last headers of execution
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get execution status code
     *
     * @return int last http status code
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get last execution error
     *
     * @return string last error message (if any)
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Test if a request header exists
     *
     * @param string $key The header key
     * @return bool
     */
    public function requestHeaderExists($key)
    {
        if (!is_array($this->headerArray)) { $this->headerArray = []; }
        if (strpos($key, ':') !== FALSE) {
            $tmp = explode(':', $key);
            $key = trim($tmp[0]);
        }
        for ($i = 0; $i < count($this->headerArray); $i++) {
            $tmp = explode(':', $this->headerArray[$i], 1);
            $key2 = trim($tmp[0]);
            if ($key2 == $key) { return TRUE; }
        }
        return FALSE;
    }

    /**
     * Add a request header
     *
     * @param string $str The header string
     * @param bool $prepend Optional flag whether to push the header on
     *  top of all other headers. Default false
     */
    public function addRequestHeader($str, $prepend = FALSE)
    {
        if (!is_array($this->headerArray)) { $this->headerArray = []; }

        $f = 0;
        if (strpos($str, ':') !== FALSE) {
            $tmp = explode(':', $str, 1);
            $key = trim($tmp[0]);
            for ($i = 0; $i < count($this->headerArray); $i++) {
                $tmp = explode(':', $this->headerArray[$i], 1);
                $key2 = trim($tmp[0]);
                if ($key2 == $key) {
                    // found a duplicate.
                    $this->headerArray[$i] = $str;
                    $f = 1;
                    break;
                }
            }
        }
        if ($f == 0) {
            if ($prepend) {
                array_unshift($this->headerArray, $str);
            }
            else {
                $this->headerArray[] = $str;
            }
        }
    }

    /**
     * Test if cURL is installed and its version is sufficient
     *
     * @return bool
     */
    public static function is_curl_suitable()
    {
        static $_curlgood = -1;

        if ($_curlgood == -1) {
            $_curlgood = 0;
            if (function_exists('curl_init') && function_exists('curl_exec')) {
                $info = curl_version();
                if (isset($info['version']) && version_compare($info['version'], '7.19.7') >= 0) {
                    $_curlgood = 1;
                }
            }
        }

        return (bool)$_curlgood;
    }

    /**
     * Execute a HTTP request
     *
     * Automatically uses fsockopen if a suitable cURL is not available.
     * And follows redirects (if so asked).
     *
     * @param string $target URL of the target page (optional)
     * @param string $referrer URL of the referrer page (optional)
     * @param string $method The request method (GET, POST or HEAD) (optional)
     * @param array $data Parameters array for GET, POST or HEAD (optional)
     * @return mixed string Response body of the target page or (post-HEAD) 'OK'/'' or FALSE
     */
    public function execute($target = '', $referrer = '', $method = '', $data = [])
    {
        // Populate the properties
        $this->target = ($target) ?: $this->target;
        $this->method = ($method) ?: $this->method;
        $this->referrer = ($referrer) ?: $this->referrer;

        // Add the new params
        if ($data && is_array($data)) {
            $this->params = array_merge($this->params, $data);
        }

        // Process data, if presented
        $queryString = '';
        if ($this->rawPostData) {
            $queryString = $this->rawPostData;
        }
        elseif ($this->params && is_array($this->params)) {
            $queryString = http_build_query($this->params, '', '&');
        }

        $this->useCurl = $this->useCurl && self::is_curl_suitable();

        // GET/HEAD methods configuration
        if ($this->method == 'GET' || $this->method == 'HEAD') {
            if ($queryString) {
                $this->target = $this->target . '?' . $queryString;
            }
        }

        // Parse target URL
        $urlParsed = parse_url($this->target);
        if ($this->port == 0 && isset($urlParsed['port']) && $urlParsed['port'] > 0) {
            $this->port = $urlParsed['port'];
        }

        // Handle SSL connection request
        if ($urlParsed['scheme'] == 'https') {
            $this->host = $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 443;
            $this->_socket = 'ssl://'.$urlParsed['host'].':'.$this->port;
        }
        else {
            $this->host = $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 80;
            $this->_socket = 'tcp://'.$urlParsed['host'].':'.$this->port;
        }

        // Finalize the target path
        $this->path   = (isset($urlParsed['path']) ? $urlParsed['path'] : '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
        $this->schema = $urlParsed['scheme'];

        // Pass the requred cookies
        $this->_passCookies();

        // Process cookies, if requested
        $cookieString = '';
        if ($this->cookies && is_array($this->cookies)) {
            // Get a blank slate
            $tempString = [];

            // Convert cookies array into a query string (eg animal=dog&sport=baseball)
            foreach ($this->cookies as $key => $value) {
                $vt = trim($value);
                if (strlen($vt) > 0) {
                    $tempString[] = $key . '=' . rawurlencode($vt);
                }
            }

            $cookieString = implode('&', $tempString);
        }

        // Will we use cURL?
        if ($this->useCurl) {
            // Initialize PHP cURL handle
            $ch = curl_init();

            // GET/HEAD method configuration
            if ($this->method == 'GET' || $this->method == 'HEAD') {
                curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
                curl_setopt($ch, CURLOPT_POST, FALSE);
                curl_setopt($ch, CURLOPT_NOBODY, $this->method == 'HEAD'); // effectively this makes the request method HEAD
            }
            else {
                // POST method configuration
                curl_setopt($ch, CURLOPT_HTTPGET, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_NOBODY, FALSE);

                if (isset($queryString)) { // might be empty
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $queryString);
                }
            }

            // Basic authentication configuration
            if ($this->username && $this->password) {
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }

            if ($this->proxy) {
                curl_setop($ch, CURL_PROXY, $this->proxy);
            }

            // Custom cookie configuration
            if ($this->useCookie) {
                // we are sending cookies.
                if (isset($cookieString)) {
                    curl_setopt ($ch, CURLOPT_COOKIE, $cookieString);
                }
                else {
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath);
                }
            }
            if ($this->saveCookie) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);     // Save cookies here
            }

            curl_setopt($ch, CURLOPT_HEADER,         TRUE);                 // No need for headers
            if (is_array($this->headerArray)) { // might be empty
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headerArray);
            }
            else {
                curl_setopt($ch, CURLOPT_HEADER,     TRUE);                 // No need of headers
            }
            curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);       // Timeout
            curl_setopt($ch, CURLOPT_USERAGENT,      $this->userAgent);     // Webbot name
            curl_setopt($ch, CURLOPT_URL,            $this->target);        // Target site
            curl_setopt($ch, CURLOPT_REFERER,        $this->referrer);      // Referer value

            curl_setopt($ch, CURLOPT_VERBOSE,        FALSE);                // Minimize logs
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);                // No certificate
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);      // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS,      $this->maxRedirect);   // Limit redirections to four
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);                 // Return in string

            // Get the target contents
            $content = curl_exec($ch);
            if ($content) {
                $tmp = explode("\r\n\r\n", $content, 2);
                for ($i = 0; $i < count($tmp); $i++) {
                    if (empty($tmp[$i])) { unset($tmp[$i]); }
                }

                if (count($tmp) > 1) {
                    // Store the contents
                    $this->result = $tmp[1];
                }
                else {
                    $this->result = '';
                }

                // Parse the headers
                $this->_parseHeaders($tmp[0]);
            }

            // Get the request info
            $info = curl_getinfo($ch);
            $this->status = $info['http_code'];

            if ($this->method == 'HEAD') {
                // No contents retrieved
                switch ((int)$this->status) {
                    case 200:
                    case 203:
                    case 226:
                    case 301:
                    case 302:
                    case 304:
                    case 307:
                    case 308:
                        $this->result = 'OK';
                        break;
                    default:
                        $this->result = ''; // failure reports falsy
                }
            }

            // Store the error (if any)
            $this->_setError(curl_error($ch));

            // Close PHP cURL handle
            if (PHP_VERSION_ID < 80500) curl_close($ch);
        }
        else { // Not using cURL
            // Get a file pointer
            $filePointer = @stream_socket_client($this->_socket, $errorNumber, $errorString, $this->timeout);

            // We have an error if pointer is not there
            if (!$filePointer) {
                $this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
                return FALSE;
            }

            // Set http headers with host, user-agent and content type
            $this->addRequestHeader($this->method .' '. $this->path. '  HTTP/1.1', TRUE);
            $this->addRequestHeader('Host: ' . $this->host);
            $this->addRequestHeader('Accept: */*');
            $this->addRequestHeader('User-Agent: ' . $this->userAgent);
            if (!$this->requestHeaderExists('Content-Type')) {
                $this->addRequestHeader('Content-Type: application/x-www-form-urlencoded');
            }

            // Specify the custom cookies
            if ($this->useCookie && $cookieString) {
                $this->addRequestHeader('Cookie: ' . $cookieString);
            }

            // POST method configuration
            if ($this->method == 'POST') {
                $this->addRequestHeader('Content-Length: ' . strlen($queryString));
            }

            // Specify the referrer
            if ($this->referrer) {
                $this->addRequestHeader('Referer: ' . $this->referrer);
            }

            // Specify http authentication (basic)
            if ($this->username && $this->password) {
                $this->addRequestheader('Authorization: Basic ' . base64_encode($this->username . ':' . $this->password));
            }

            $this->addRequestHeader('Connection: close');

            $requestHeader = implode("\r\n", $this->headerArray) . "\r\n\r\n";
            // POST method configuration
            if ($this->method == 'POST') {
                $requestHeader .= $queryString;
            }

            // We're ready to launch
            fwrite($filePointer, $requestHeader);


            // Clean the slate
            $responseHeader = '';
            $responseContent = '';

            // 3...2...1...Launch !
            $n = 0;
            do {
                $responseHeader .= fread($filePointer, 1);
            } while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader) && !feof($filePointer));

            // Parse the headers
            $this->_parseHeaders($responseHeader);

            // Do we have a 301/302 redirect ?
            if (($this->status == '301' || $this->status == '302') && $this->redirect) {
                if ($this->curRedirect < $this->maxRedirect) {
                    // Let's find out the new redirect URL
                    $newUrlParsed = parse_url($this->headers['location']);

                    if ($newUrlParsed['host']) {
                        $newTarget = $this->headers['location'];
                    }
                    else {
                        $newTarget = $this->schema . '://' . $this->host . '/' . $this->headers['location'];
                    }

                    // Reset some properties
                    $this->port   = 0;
                    $this->status = 0;
                    $this->params = [];
//                  $this->method = 'POST'; //TODO
                    $this->referrer = $this->target;

                    // Increase the redirect counter
                    $this->curRedirect++;

                    // Let's go, go, go !
                    $this->result = $this->execute($newTarget);
                }
                else {
                    $this->_setError('Too many redirects.');
                    return FALSE;
                }
            }
            else {
                // Nope...so unless it's a HEAD request, get the rest of the contents (non-chunked)
                if ($this->method != 'HEAD') {
                    if (!isset($this->headers['transfer-encoding']) || $this->headers['transfer-encoding'] != 'chunked') {
                        while (!feof($filePointer)) {
                            $responseContent .= fgets($filePointer, 128);
                        }
                    }
                    else {
                        // Get the contents (chunked)
                        while (!feof($filePointer) && $chunkLength = hexdec(fgets($filePointer))) {
                            $responseContentChunk = '';
                            $readLength = 0;

                            while ($readLength < $chunkLength) {
                                $responseContentChunk .= fread($filePointer, $chunkLength - $readLength);
                                $readLength = strlen($responseContentChunk);
                            }

                            $responseContent .= $responseContentChunk;
                            fgets($filePointer);
                        }
                    }

                    // Store the retrieved content
                    $this->result = chop($responseContent);
                    $this->status = (isset($this->result[0])) ? 200 : 400; //TODO OR other func(responseContent)
                }
                elseif (preg_match('/Connection:[ \t]*close/i', $responseHeader) &&
                    (!preg_match('/Content\-Length:[ \t](\d+)/i', $responseHeader, $matches) ||
                     isset($matches[1]) && $matches[1] > 0) &&
                    preg_match('/Content\-Type:[ \t]/i', $responseHeader) ) {
                        $this->result = 'OK';
                        $this->status = 200;
                }
                else {
                    $this->result = ''; // failure reports falsy
                    $this->status = 400;
                }
                fclose($filePointer);
            }
        }

        // There it is! We have it!! Return to base !!!
        return $this->result;
    }

    /**
     * Parse headers
     * @access private
     * @internal
     *
     * Parse the response headers and store them for finding the resposne
     * status, redirection location, cookies, etc.
     *
     * @param string $responseHeader Raw header response
     */
    private function _parseHeaders($responseHeader)
    {
        // Break up the headers
        $headers = explode("\r\n", $responseHeader);

        // Clear the header array
        $this->_clearHeaders();

        // Get response status
        if ($this->status == 0) {
            // Oooops !
            if (!preg_match('/HTTP\/\d+(?:\.\d+)?[ \t]+(\d+)[ \t]/i', $headers[0], $matches)) {
                $this->_setError('Unexpected HTTP response status');
                return FALSE;
            }

            // Gotcha!
            $this->status = $matches[1];
            array_shift($headers);
        }

        // Prepare all the other headers
        foreach ($headers as $header) {
            // Get name and value
            $headerName  = strtolower($this->_tokenize($header, ':'));
            $headerValue = trim(chop($this->_tokenize("\r\n")));

            // If it's already there, then add as an array. Otherwise, just keep there
            if (isset($this->headers[$headerName])) {
                if (!is_array($this->headers[$headerName])) {
                    $this->headers[$headerName] = [$this->headers[$headerName]];
                }
                $this->headers[$headerName][] = $headerValue;
            }
            else {
                $this->headers[$headerName] = $headerValue;
            }
        }

        // Save cookies if asked
        if ($this->saveCookie && isset($this->headers['set-cookie'])) {
            $this->_parseCookie();
        }
    }

    /**
     * Clear the headers array
     *
     * @internal
     * @access private
     */
    private function _clearHeaders()
    {
        $this->headers = [];
    }

    /**
     * Parse the set-cookie headers from response and add them for inclusion.
     *
     * @access private
     * @internal
     */
    private function _parseCookie()
    {
        // Get the cookie header(s) as array
        if (isset($this->headers['set-cookie']) ) {
            if (is_array($this->headers['set-cookie'])) {
                $cookieHeaders = $this->headers['set-cookie'];
            }
            else {
                $cookieHeaders = [$this->headers['set-cookie']];
            }
        }
        else {
            return;
        }

        // Loop through the array
        for ($cookie = 0; $cookie < count($cookieHeaders); $cookie++) {
            $cookieName  = trim($this->_tokenize($cookieHeaders[$cookie], '='));
            $cookieValue = $this->_tokenize(';');

            $urlParsed   = parse_url($this->target);

            $domain      = $urlParsed['host'];
            $secure      = '0';

            $path        = '/';
            $expires     = '';

            while (($name = trim(urldecode($this->_tokenize('=')))) != '') {
                $value = urldecode($this->_tokenize(';'));

                switch ($name) {
                    case 'path': $path = $value; break;
                    case 'domain': $domain = $value; break;
                    case 'secure': $secure = ($value) ? '1' : '0'; break;
                }
            }

            $this->_setCookie($cookieName, $cookieValue, $expires, $path, $domain, $secure);
        }
    }

    /**
     * Set cookie
     *
     * Populate the internal _cookies array for future inclusion in
     * subsequent requests. This validates and then populates the
     * object properties with an associative array for cookie.
     * @access private
     * @internal
     *
     * @param string Cookie name
     * @param string Cookie value
     * @param string Optional cookie expiry date
     * @param string Cookie path Default '/'
     * @param string Cookie domain
     * @param string Cookie security (0 = non-secure, 1 = secure) Default 0
     */
    private function _setCookie($name, $value, $expires = '', $path = '/', $domain = '', $secure = 0)
    {
        foreach ([
        'name' => $name,
        'path' => $path,
        'domain' => $domain
        ] as $label => $val) {
            if ($val === '' || $val === null) {
                return($this->_setError("No cookie $label was specified."));
            }
        }

        if ($path[0] != '/') {
            return($this->_setError("'$path' is not a valid path for cookie $name."));
        }

        if (strpos($domain, '.', (($domain[0] == '.') ? 1 : 0)) === FALSE) {
            return($this->_setError("'$domain' is not a valid domain for cookie $name."));
        }

        $domain = strtolower($domain);

        if ($domain[0] == '.') {
            $domain = substr($domain, 1);
        }

        $name  = $this->_encodeCookie($name, TRUE);
        $value = $this->_encodeCookie($value, FALSE);
        $secure = (int)$secure;

        $this->_cookies[] = ['name'    => $name,
                             'value'   => $value,
                             'domain'  => $domain,
                             'path'    => $path,
                             'expires' => $expires,
                             'secure'  => $secure];
    }

    /**
     * Encode cookie name/value (internal)
     *
     * @param string $value Value of cookie to encode
     * @param string $name Name of cookie to encode
     * @return string encoded string
     * @access private
     * @internal
     */
    private function _encodeCookie($value, $name)
    {
        if ($name) {
            return str_replace('=', '%25', $value);
        }
        return str_replace(';', '%3B', $value);
    }

    /**
     * Pass cookies (internal)
     *
     * Get the cookies which are valid for the current request. Checks
     * domain and path to decide the return.
     *
     * @access private
     */
    private function _passCookies()
    {
        if ($this->_cookies && is_array($this->_cookies)) {
            $urlParsed = parse_url($this->target);
            $tempCookies = [];

            foreach ($this->_cookies as $cookie) {
                if ($this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
                    && (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')) {
                    $tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
                }
            }

            // cookies with longer paths go first
            foreach ($tempCookies as $name => $values) {
                krsort($values);
                foreach ($values as $value) {
                    $this->addCookie($name, $value);
                }
            }
        }
    }

    /**
     * Check if cookie domain matches a request host (internal)
     *
     * $cookieDomain must contain at least one '.' char, or at least
     * two of them if it begins with a '.' char.
     *
     * @param string $requestHost Request host
     * @param string $cookieDomain Cookie domain
     * @return bool Match success
     * @access private
     * @internal
     */
    private function _domainMatch($requestHost, $cookieDomain)
    {
        if ('.' != $cookieDomain[0]) {
            return $requestHost == $cookieDomain;
        }
        elseif (substr_count($cookieDomain, '.') > 1) {
            return substr('.' . $requestHost, - strlen($cookieDomain)) == $cookieDomain;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Tokenize string for various internal usage. Omit the second parameter
     * to tokenize the previous string that was provided in the prior call to
     * the function.
     * @access private
     * @internal
     *
     * @param string $string The string to tokenize
     * @param string $separator The separator to use
     * @return string Tokenized string
     */
    private function _tokenize($string, $separator = '')
    {
        if ($separator == '') {
            $separator = $string;
            $string = $this->nextToken;
        }

        for ($character = 0, $nc = strlen($separator); $character < $nc; $character++) {
            if (($position = strpos($string, $separator[$character])) !== FALSE) {
                $found = (isset($found)) ? min($found, $position) : $position;
            }
        }

        if (isset($found)) {
            $this->nextToken = substr($string, $found + 1);
            return substr($string, 0, $found);
        }
        else {
            $this->nextToken = '';
            return $string;
        }
    }

    /**
     * Set error message
     * @access private
     * @internal
     *
     * @param string $error Error message
     * @return mixed string verbatim $error or null
     */
    private function _setError($error)
    {
        if ($error) {
            $this->error = $error;
            return $error;
        }
    }
}
