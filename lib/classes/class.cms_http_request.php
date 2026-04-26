<?php
/**
 * cms_http_request class
 *
 * This is a wrapper class that uses either cURL or stream_socket_client
 * to interact with the www. If cURL is to be used, version 7.19.7 (Nov 2009)
 * or higher is required.
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
 * $result = $http->send();
 * </pre>
 *
 * @package     CMS
 * @license     GPL
 * @author      Md Emran Hasan
 */
class cms_http_request
{
    /**
     * Request target (URL)
     *
     * @var string
     */
    protected $target;

    /**
     * Target host
     *
     * @var string
     */
    protected $host;

    /**
     * Target port
     *
     * @var int
     */
    protected $port;

    /**
     * Target path
     *
     * @var string
     */
    protected $path;

    /**
     * Target schema
     *
     * @var string
     */
    protected $schema;

    /**
     * Request http method ('GET', 'POST' or 'HEAD')
     *
     * @var string
     */
    protected $method;

    /**
     * Raw POST data
     *
     * @var urlencoded string like 'para1=val1&para2=val2&...' or empty
     */
    protected $rawPostData;

    /**
     * Parameters for the request
     * (not for this object's setup)
     *
     * @var array
     */
    protected $params;

    /**
     * Cookies for the request, supplied via addCookie()
     *
     * @var array
     */
    protected $cookies;

    /**
     * Cookie headers parsed from request response, populated via _setCookie()
     *
     * @var array
     */
    protected $_cookies;

    /**
     * Number of seconds to timeout
     *
     * @var int
     */
    protected $timeout;

    /**
     * Socket identifier
     *
     * @var string
     */
    protected $socket;

    /**
     * Whether to use cURL
     * If true, but a sufficient cURL version is not installed, cURL
     * will not be used.
     *
     * @var bool
     */
    protected $useCurl;

    /**
     * Whether to do certificate-checks when using a secure url
     * @since 2.2.23F2
     *
     * @var bool
     */
    protected $checkSecure;

    /**
     * Referrer URL
     *
     * @var string
     */
    protected $referrer;

    /**
     * User agent string
     *
     * @var string
     */
    protected $userAgent;

    /**
     * Cookie path (to be used with cURL)
     *
     * @var string
     */
    protected $cookiePath;

    /**
     * Whether to use cookies at all
     *
     * @var bool
     */
    protected $useCookie;

    /**
     * Whether to store cookies for subsequent requests
     *
     * @var bool
     */
    protected $saveCookie;

    /**
     * Username (for authentication)
     *
     * @var string
     */
    protected $username;

    /**
     * Password (for authentication)
     *
     * @var string
     */
    protected $password;

    /**
     * Fetched target content
     *
     * @var string
     */
    protected $result;

    /**
     * Latest headers
     *
     * @var string
     */
    protected $headers;

    /**
     * Latest call's http status code
     *
     * @var string
     */
    protected $status;

    /**
     * Whether to follow http redirects
     *
     * @var bool
     */
    protected $redirect;

    /**
     * Maximum number of redirect to follow
     *
     * @var integer
     */
    protected $maxRedirect;

    /**
     * The current number of redirects
     *
     * @var int
     */
    protected $curRedirect;

    /**
     * Error message if any
     *
     * @var string
     */
    protected $error;

    /**
     * Next portion of a string to be tokenised
     *
     * @var string
     */
    protected $nextToken;

    /**
     * Whether debugging is enabled
     *
     * @var bool
     */
    protected $debug;

    /**
     * Optional http headers
     *
     * @var array
     */
    protected $headerArray;

    /**
     * Debug messages
     *
     * @var array
     * @todo will keep debug messages
     */
    protected $debugMsg;

    /**
     * Proxy information (host:port)
     *
     * @var string
     */
    protected $proxy;

    /**
     * Constructor
     *
     * @param array $params Optional settings
     */
    public function __construct($params = [])
    {
        if ($params) {
            $this->initialize($params);
        }
        else {
            $this->clear();
        }
    }

    /**
     * Set this object's properties in acccord with class defaults and
     * the supplied $params, if any.
     * Values might be set directly, or via a corresponding 'set*' method.
     *
     * @param array $params Optional settings
     */
    public function initialize($params = [])
    {
        $this->clear();
        foreach ($params as $key => $val) {
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
     * Revert all object-properties to their default value.
     */
    public function clear()
    {
        // Set the request defaults
        $this->host        = '';
        $this->port        = 0;
        $this->path        = '';
        $this->target      = '';
        $this->method      = 'GET';
        $this->schema      = 'http';
        $this->params      = [];
        $this->headers     = [];
        $this->headerArray = [];
        $this->proxy       = null; // mixed bool|int|string|array|resource

        // Set the config details
        $this->debug       = FALSE;
        $this->error       = '';
        $this->status      = 0;
        $this->timeout     = 25;
        $this->useCurl     = TRUE; // and if a suitable version is present
        $this->checkSecure = TRUE;
        $this->referrer    = CMS_ROOT_URL;
        $this->username    = '';
        $this->password    = '';
        $this->redirect    = TRUE;
        $this->maxRedirect = 3;
        $this->result      = null; // mixed
        $this->nextToken   = '';

        // Set the cookie and agent defaults
        $this->_cookies    = [];
        $this->cookies     = [];
        $this->useCookie   = TRUE;
        $this->saveCookie  = TRUE;
        $this->cookiePath  = PUBLIC_CACHE_LOCATION.'/c'.md5(session_id().__CLASS__).'.dat'; // by default, use a session-specific cookie file
        $this->userAgent   = $_SERVER['HTTP_USER_AGENT'] .' CMSMS/'.CMS_VERSION;
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
     * Set whether to use cURL
     *
     * @param bool $value Whether or not to use cURL
     */
    public function useCurl($value = TRUE)
    {
        if (is_bool($value)) { $this->useCurl = $value; }
    }

    /**
     * Set whether to check certificate status
     *
     * @param bool $value Whether or not to check
     */
    public function checkSecure($value = TRUE)
    {
        if (is_bool($value)) { $this->checkSecure = $value; }
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
     * @param string $data urlencoded string like 'para1=val1&para2=val2&...' or empty
     */
    public function setRawPostData($data)
    {
        $this->setMethod('POST');
        $this->rawPostData = $data;
    }

    /**
     * Set request parameters
     *
     * @param array or string $data Request parameter(s)
     */
    public function setParams($data)
    {
        if (is_array($data)) {
            $this->params = array_merge($this->params, $data);
        }
        else {
            $this->setRawPostData($data);
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
     * Get the (untranslated) string corresponding to the supplied error code
     * Adapted from https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @since 2.2.23F2
     *
     * @param int $code http response code
     * @return string maybe empty
     */
    public static function http_message($code)
    {
        switch ($code) {
        case 100: return 'Continue';
        case 101: return 'Switching Protocols';
        case 102: return 'Processing';
        case 103: return 'Early Hints';
        case 104: return 'Upload Resumption Supported';
        case 200: return 'OK';
        case 201: return 'Created';
        case 202: return 'Accepted';
        case 203: return 'Non-Authoritative Information';
        case 204: return 'No Content';
        case 205: return 'Reset Content';
        case 206: return 'Partial Content';
        case 207: return 'Multi-Status';
        case 208: return 'Already Reported';
        case 226: return 'IM Used';
        case 300: return 'Multiple Choices';
        case 301: return 'Moved Permanently';
        case 302: return 'Found';
        case 303: return 'See Other';
        case 304: return 'Not Modified';
        case 305: return 'Use Proxy';
        case 307: return 'Temporary Redirect';
        case 308: return 'Permanent Redirect';
        case 400: return 'Bad Request';
        case 401: return 'Unauthorized';
        case 402: return 'Payment Required';
        case 403: return 'Forbidden';
        case 404: return 'Not Found';
        case 405: return 'Method Not Allowed';
        case 406: return 'Not Acceptable';
        case 407: return 'Proxy Authentication Required';
        case 408: return 'Request Timeout';
        case 409: return 'Conflict';
        case 410: return 'Gone';
        case 411: return 'Length Required';
        case 412: return 'Precondition Failed';
        case 413: return 'Content Too Large';
        case 414: return 'URI Too Long';
        case 415: return 'Unsupported Media Type';
        case 416: return 'Range Not Satisfiable';
        case 417: return 'Expectation Failed';
        case 421: return 'Misdirected Request';
        case 422: return 'Unprocessable Content';
        case 423: return 'Locked';
        case 424: return 'Failed Dependency';
        case 425: return 'Too Early';
        case 426: return 'Upgrade Required';
        case 427: return 'Unassigned';
        case 428: return 'Precondition Required';
        case 429: return 'Too Many Requests';
        case 430: return 'Unassigned';
        case 431: return 'Request Header Fields Too Large';
        case 451: return 'Unavailable For Legal Reasons';
        case 500: return 'Internal Server Error';
        case 501: return 'Not Implemented';
        case 502: return 'Bad Gateway';
        case 503: return 'Service Unavailable';
        case 504: return 'Gateway Timeout';
        case 505: return 'HTTP Version Not Supported';
        case 506: return 'Variant Also Negotiates';
        case 507: return 'Insufficient Storage';
        case 508: return 'Loop Detected';
        case 509: return 'Unassigned';
        case 510: return 'Not Extended'; //OBSOLETE
        case 511: return 'Network Authentication Required';
        default: return '';
        }
    }

    /**
     * Execute a HTTP request
     * @deprecated since 2.2.23F2 Instead use send()
     *
     * @param string $target URL of the target page Default ''
     * @param string $referrer URL of the referrer page Default ''
     * @param string $method Request method (GET, POST or HEAD) Default ''
     * @param array $data Parameters array for GET, POST or HEAD Default []
     * @return mixed
     */
    public function execute($target = '', $referrer = '', $method = '', $data = [])
    {
       if ($referrer) { $this->referrer = $referrer; }
       if ($method) { $this->setMethod($method); }
       return $this->send($target, $data);
    }

    /**
     * Execute a HTTP request.
     * Automatically uses fsockopen if a suitable cURL is not available.
     * And follows redirects (if enabled and needed).
     * @since 2.2.23F2
     *
     * @param string $target URL of the target page Default ''
     * @param array $data Parameters for GET, POST or HEAD Default []
     * @return mixed string Response body generated by target or (post-HEAD) 'OK'/'' or FALSE
     */
    public function send($target = '', $data = [])
    {
        // Populate the properties
        if ($target) { $this->target = $target; }

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

        // GET/HEAD methods configuration
        if ($this->method == 'GET' || $this->method == 'HEAD') {
            if ($queryString) {
                $this->target .= '?' . $queryString;
            }
        }

        // Parse target URL
        $urlParsed = parse_url($this->target);
        if ($this->port == 0 && isset($urlParsed['port']) && $urlParsed['port'] > 0) {
            $this->port = $urlParsed['port'];
        }

        $this->host = $urlParsed['host'];
        if ($urlParsed['scheme'] == 'https') {
            $this->port = ($this->port != 0) ? $this->port : 443;
            $this->socket = 'ssl://'.$urlParsed['host'].':'.$this->port;
        }
        else {
            $this->port = ($this->port != 0) ? $this->port : 80;
            $this->socket = 'tcp://'.$urlParsed['host'].':'.$this->port;
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
        if ($this->useCurl && self::is_curl_suitable()) {
            // Initialize PHP cURL handle
            $ch = curl_init();

            // GET/HEAD method configuration
            if ($this->method == 'GET' || $this->method == 'HEAD') {
                curl_setopt_array($ch, [
                 CURLOPT_HTTPGET => TRUE,
                 CURLOPT_POST => FALSE,
                 CURLOPT_NOBODY => ($this->method == 'HEAD')]); // TRUE makes the request method HEAD
            }
            else {
                // POST method configuration
                curl_setopt_array($ch, [
                 CURLOPT_HTTPGET => FALSE,
                 CURLOPT_POST => TRUE,
                 CURLOPT_POSTFIELDS => $queryString,// needed even if empty
                 CURLOPT_NOBODY => FALSE]);
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
                if (!empty($cookieString)) {
                    curl_setopt ($ch, CURLOPT_COOKIE, $cookieString);
                }
                elseif( $this->cookiePath ) {
                    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookiePath); // Empty path is valid
                }
            }
            if ($this->saveCookie) {
                curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiePath);     // Save cookies here
            }

            if ($this->headerArray && is_array($this->headerArray)) { // might be empty
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headerArray);
            }
            curl_setopt_array($ch, [
             CURLOPT_TIMEOUT        => $this->timeout,
             CURLOPT_USERAGENT      => $this->userAgent,
             CURLOPT_URL            => $this->target,
             CURLOPT_REFERER        => $this->referrer,
             CURLOPT_VERBOSE        => FALSE,
             CURLOPT_FOLLOWLOCATION => $this->redirect && $this->maxRedirect > 0,
             CURLOPT_MAXREDIRS      => $this->maxRedirect, // Limit redirections
             CURLOPT_HEADER         => TRUE, // No writefunction callback BUT we want to parse the headers in $content, below
             CURLOPT_RETURNTRANSFER => TRUE
            ]);
            if (($this->debug || !$this->checkSecure) && $urlParsed['scheme'] == 'https') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  // No certificate-match
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // No certificate check
            }

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
                    $this->result = ''; // failure reports falsy
                }
                // Parse the headers
                $this->_parseHeaders($tmp[0]);
            }
            else {
                $this->result = ''; // failure reports falsy
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
            $this->_setError(curl_error($ch)); // error message if any

            // Close PHP cURL handle
            if (PHP_VERSION_ID < 80500) curl_close($ch);
        }
        else {// Not using cURL
            if (!function_exists('stream_socket_client')) {
                // Allegedly, some sitehosts disable that function
                $this->_setError('Cannot access stream socket');
                return FALSE;
            }
            if ($urlParsed['scheme'] != 'https') {
                $context = stream_context_create();
            }
            else {
                $opts = stream_get_transports();
                if (in_array('tls', $opts)) {
                    $transport = 'tls';
                }
                elseif (in_array('ssl', $opts)) {
                    $transport = 'ssl'; // try sslv2 and sslv3
                }
                else {
                    $this->_setError('No suitable stream transport');
                    return FALSE;
                }
                $check = !($this->debug || !$this->checkSecure);
                $opts = [
                    $transport => [
                        'allow_self_signed' => true,
                        'verify_host' => $check,
                        'verify_peer' => $check
                    ]
                ];
                $context = stream_context_create($opts);
            }

            // Try to get a connection
            $filePointer = @stream_socket_client($this->socket, $errorNumber, $errorString, $this->timeout, STREAM_CLIENT_ASYNC_CONNECT, $context);

            // Error if pointer is not there
            if (!$filePointer) {
                $this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
                return FALSE;
            }

            // Set http headers with host, user-agent and content type
            $this->addRequestHeader($this->method .' '. $this->path. ' HTTP/1.1', TRUE);
            $this->addRequestHeader('Host: ' . $this->host);
            if (!$this->requestHeaderExists('Accept')) {
                $this->addRequestHeader('Accept: */*');
            }
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

            $this->addRequestHeader('Connection: close'); // HTTP/1.1 only

            $requestContent = implode("\r\n", $this->headerArray) . "\r\n\r\n";
            // POST method configuration
            if ($this->method == 'POST' && ($queryString || is_numeric($queryString))) {
                $requestContent .= $queryString ."\r\n";
            }

            // We're ready to launch
            fwrite($filePointer, $requestContent);

            $responseHeader = '';
            do {
                $line = fgets($filePointer);
                if ($line[0] != "\r") {
                    $responseHeader .= $line;
                }
                else {
                    break;
                }
            } while (!feof($filePointer));

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
                    $this->result = $this->send($newTarget);
                }
                else {
                    $this->_setError('Too many redirects.');
                    return FALSE;
                }
            }
            else {
                // Nope...so unless it's a HEAD request, get the rest of the contents (non-chunked)
                if ($this->method != 'HEAD') {
                    $responseContent = '';
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
                    $this->result = rtrim($responseContent);
//                  $this->status = (isset($this->result[0])) ? 200 : 400; 0-length result might be valid
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
        return $this->result;
    }

    /**
     * Parse the response headers and store them for finding the response
     * status, redirection location, cookies, etc.
     * @access private
     * @internal
     *
     * @param string $responseHeader Raw header response
     */
    private function _parseHeaders($responseHeader)
    {
        // Clear the header array
        $this->_clearHeaders();

        if (!$responseHeader) {
            $this->_setError('Unexpected HTTP response no header');
            return;
        }
        // Break up the headers
        $headers = explode("\r\n", $responseHeader);

        // Get response status
        if ($this->status == 0) {
            if (!preg_match('/HTTP\/\d+(?:\.\d+)?[ \t]+(\d+)[ \t]/i', $headers[0], $matches)) {
                // Oooops !
                $this->_setError('Unexpected HTTP response status');
                return;
            }
            // Gotcha!
            $this->status = $matches[1];
            array_shift($headers);
        }

        // Prepare all the other headers
        foreach ($headers as $header) {
            // Get name and value
            $headerName = strtolower($this->_tokenize($header, ':'));
            if ($headerName == '') continue;
            $headerValue = trim(rtrim($this->_tokenize("\r\n")));
            if ($headerValue == '') continue;

            // If it's already there, then add as an array. Otherwise, just add
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
     * Populate the internal _cookies array for inclusion in subsequent
     * requests. This validates and then populates the object properties
     * with an associative array for cookie.
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

        $this->_cookies[] = [
         'name'    => $name,
         'value'   => $value,
         'domain'  => $domain,
         'path'    => $path,
         'expires' => $expires,
         'secure'  => $secure
        ];
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
     * Tokenize the supplied string.
     * Omit the $separator argument to tokenize the string that was
     * provided to the prior call to this function.
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
     * @return string verbatim $error possibly empty
     */
    private function _setError($error)
    {
        if ($error) {
            $this->error = $error;
            return $error;
        }
        return '';
    }
}
