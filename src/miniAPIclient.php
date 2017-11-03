<?php

namespace ierusalim\miniAPI;

/**
 * This class contiains minAPIsrv
 *
 * PHP Version >= 5.5
 *
 * @package    ierusalim\minAPIsrv
 * @author     Alexander Jer <alex@ierusalim.com>
 * @copyright  2017, Ierusalim
 * @license    https://opensource.org/licenses/Apache-2.0 Apache-2.0
 */

class miniAPIclient
{
    /*
     * Trait for async requests
     * Uncomment following string if you really need async-mode
     */
    //use miniAPIslots;

    /**
     * Protocol for access to minimal-API server
     *
     * @var string http or https
     */
    public $scheme = 'http';

    /**
     * minimal-API server IP or host name
     *
     * @var string
     */
    public $host = '127.0.0.1';

    /**
     * minimal-API server TCP/IP-port
     *
     * @var integer
     */
    public $port = 80;


    /**
     * path for minimal-API server (part of URL with "/")
     *
     * @var string
     */
    public $path = '/';

    /**
     * Auth parameters array (if server required authorization)
     *
     * @var string|null
     */
    private $auth = [];

    /**
     * minimal-API server full-URL as scheme://host:port/
     *
     * @var string|null
     */
    private $server_url;

    /**
     * get-options for each request. Array [option => value]
     *
     * @var array
     */
    public $options = [];

    /**
     * CURL options for each request
     *
     * @var array
     */
    public $curl_options = [
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_USERAGENT => "PHP-minAPI",

        //Set 2 if server have valid ssl-sertificate
        \CURLOPT_SSL_VERIFYHOST => 0,
        \CURLOPT_SSL_VERIFYPEER => false,

        // time limits in seconds
        \CURLOPT_CONNECTTIMEOUT => 0,
        \CURLOPT_TIMEOUT_MS => 99999,

        \CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

        // optional, if the request-headers sent is interesting
        \CURLINFO_HEADER_OUT => true,
    ];

    /**
     * Parameters, interesting for get by function curl_getinfo after request.
     *
     * @var array|false
     */
    public $curl_info = [
        // must be present
        \CURLINFO_HTTP_CODE => 0,
        \CURLINFO_HEADER_SIZE =>0,

        \CURLINFO_CONTENT_TYPE => 0,

        // optional: size of compressed response (or full size if compression is off)
        \CURLINFO_SIZE_DOWNLOAD => 0,

        // optional: size of body-data sent
        \CURLINFO_SIZE_UPLOAD => 0,

        // optional, if the request-headers sent is interesting
        \CURLINFO_HEADER_OUT => 0,
    ];

    /**
     * Last error reported by CURL or empty string if no errors
     *
     * @var string
     */
    public $last_curl_error_str = '';

    /**
     * HTTP-Code from last server response
     *
     * @var mixed
     */
    public $last_code;

    /**
     * Set true for show sending requests and server answers
     *
     * @var boolean
     */
    public $debug = false;

    /**
     * Last sent query or query for sending if when explicitly not specified
     *
     * @var string
     */
    public $query = 'test';

    /**
     * Name of http-get parameter for query-request, or empty
     *
     * Set empty for send query as addition of api-url-path (without '?')
     *
     * @var string
     */
    public $query_name = 'query';

    /**
     * Hook on doApiCall executing (before send request, can modify url)
     *
     * @var callable|false
     */
    public $hook_before_api_call = false;

    /**
     * True if running under windows, false otherwise
     *
     * @var boolean
     */
    public $is_windows;

    /**
     * File handler for downloading file
     *
     * @var resource|null
     */
    public $fh;

    /**
     * Results of last ->query(request)
     * Contains string with error description or data non-empty server response.
     * If no errors and server response is empty, this value not changed.
     *
     * @var string
     */
    public $results;


    /**
     * Two formats are supported for set server parameters:
     *
     *  1) new miniAPIclient($server_url [, $user, $pass]);
     *
     *  2) new miniAPIclient($host, $port [, $user, $pass]);
     *
     * Also, server parameters may be set late by ->setServerUrl($url)
     *
     * Example:
     *  $h = new miniAPIclient;
     *  $h->setServerUrl("https://user:pass@127.0.0.1:443/");
     *
     * @param string|null $host_or_full_url Host name or Full server URL
     * @param integer|null $port TCP-IP server port
     * @param string|null $user user for authorization (if need)
     * @param string|null $pass password for authorization (if need)
     */
    public function __construct(
        $host_or_full_url = null,
        $port = null,
        $user = null,
        $pass = null
    ) {
        $this->is_windows = \DIRECTORY_SEPARATOR !== '/';

        if (!empty($host_or_full_url)) {
            if (\strpos($host_or_full_url, '/')) {
                $this->setServerUrl($host_or_full_url);
            } else {
                $this->host = $host_or_full_url;
            }
        }
        if (!empty($port)) {
            $this->port = $port;
        }
        if (!is_null($user)) {
            $this->auth['user'] = $user;
        }
        if (!is_null($pass)) {
            $this->auth['pass'] = $pass;
        }

        $this->setCompression(true);

        $this->setServerUrl();
    }

    /**
     * Set server connection parameters from url
     *
     * Object-oriented style, return $this if ok, throw \Exception on errors
     *
     * Example:
     * - Set scheme=http, host=127.0.0.1, port=8123, user=default, pass=[empty]
     * - setServerUrl("http://default:@127.0.0.1:8123/");
     *
     * @param string|null $full_server_url Full server URL
     * @throws \Exception
     */
    public function setServerUrl($full_server_url = null)
    {
        if (!empty($full_server_url)) {
            $p_arr = \parse_url($full_server_url);
            foreach (['scheme' => '', 'host' => '', 'port' => 80, 'path' => '/'] as $p => $default) {
                $this->$p = isset($p_arr[$p]) ? $p_arr[$p] : $default;
            }
            foreach (['user', 'pass'] as $p) {
                if (isset($this->auth[$p]) || isset($p_arr[$p])) {
                    $this->auth[$p] = isset($p_arr[$p]) ? $p_arr[$p] : null;
                }
            }
        }

        if (empty($this->scheme) ||
            empty($this->host) ||
            empty($this->port) ||
            !in_array($this->scheme, ['http', 'https'])
        ) {
            throw new \Exception("Illegal server parameters");
        }
        $this->server_url = $this->scheme . '://' . $this->host . ':' . $this->port
            . (empty($this->path) ? '/' : $this->path);

        return $this;
    }

    /**
     * This function need because $this->auth is private.
     *
     * @param array $auth_arr_for_compare
     * @return boolean
     */
    public function checkAuthPars($auth_arr_for_compare) {
        return $auth_arr_for_compare === $this->auth;
    }

    /**
     * Set http-compression mode on/off
     *
     * @param boolean $true_or_false
     */
    public function setCompression($true_or_false)
    {
        if ($true_or_false) {
            $this->setOption('enable_http_compression', 1);
            $this->curl_options[\CURLOPT_ENCODING] = 'gzip';
        } else {
            $this->setOption('enable_http_compression', null);
            unset($this->curl_options[\CURLOPT_ENCODING]);
        }
    }

    /**
     * Set addition http-request parameter.
     *
     * @param string $key option name
     * @param string|null $value option value
     * @param boolean $overwrite true = set always, false = set only if not defined
     * @return string|null Return old value (or null if old value undefined)
     */
    public function setOption($key, $value, $overwrite = true)
    {
        $old_value = isset($this->options[$key]) ? $this->options[$key] : null;
        if (\is_null($old_value) || $overwrite) {
            if (\is_null($value)) {
                unset($this->options[$key]);
            } else {
                $this->options[$key] = $value;
            }
        }
        return $old_value;
    }

    /**
     * Get http-request parameter that was set via setOption
     *
     * @param string $key option name
     * @return string|null Return option value or null if option not defined
     */
    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    /**
     * Delete http-option by specified key
     *
     * @param string $key Option name
     * @return string|null Return old value of deleted option
     */
    public function delOption($key)
    {
        $old_value = $this->getOption($key);
        unset($this->options[$key]);
        return $old_value;
    }

    /**
     * Object-style ->query($req [,$post_data])->query(...)
     *
     * Sends query to server (always in POST-mode)
     * - If server response not empty, places results to $this->results.
     * - Note that there is an empty string at the end of the response line \n
     * - Note that if server return an empty result and the value $this->results does not change
     *
     * Throws an exception if there is an error. Return $this-object if not error.
     *
     * @param string $sql SQL-query
     * @param array|string|null $post_data Parameters send in request body
     * @return $this
     * @throws \Exception
     */
    public function query($sql, $post_data = null)
    {
        $to_slot = isset($this->to_slot) ? $this->to_slot : '';
        $ans = $this->postQuery($sql, $post_data);
        if (empty($to_slot)) {
            if (!empty($ans['curl_error'])) {
                $this->results = $ans['curl_error'];
                throw new \Exception($this->results);
            }
            if (!empty($ans['response'])) {
                $this->results = $ans['response'];
            }
            if ($ans['code'] != 200) {
                throw new \Exception(\substr($ans['response'], 0, 2048));
            }
        }
        return $this;
    }

    /**
     * Send Get query if $post_data is empty, otherwise send Post query
     * This is a multiplexor for functions getQuery|postQuery
     *
     * @param string            $h_query Parameters send in http-request after "?"
     * @param array|string|null $post_data Parameters send in request body
     * @return array
     */
    public function anyQuery($h_query, $post_data = null)
    {
        return
            \is_null($post_data) ?
            $this->getQuery($h_query) : $this->postQuery($h_query, $post_data)
        ;
    }

    /**
     * Send Get API-query
     *
     * Function is envelope for doQuery
     *
     * @param string|null $query
     * @return array
     */
    public function getQuery($query = null)
    {
        return $this->doQuery($query, false, null);
    }

    /**
     * Send Post API-query
     *
     * Function is envelope for doQuery
     *
     * @param string|null $query
     * @param array|string|null $post_data
     * @param string|null $file
     * @return array
     */
    public function postQuery(
        $query = null,
        $post_data = null,
        $file = null
    ) {
        return $this->doQuery($query, true, $post_data, $file);
    }

    /**
     * Send Get or Post API-query depends of $is_post parameter
     *
     * Function is envelope for doApiCall
     *
     * @param string $query Request for send to server
     * @param boolean $is_post true for send POST-request, false for GET
     * @param array|string|null $post_data for send in POST-request body
     * @param string|null $file file name (full name with path) for send
     * @return array
     */
    public function doQuery(
        $query = null,
        $is_post = false,
        $post_data = null,
        $file = null,
        $put = false
    ) {
        if (\is_null($query)) {
            $query = $this->query;
        } else {
            $this->query = $query;
        }

        $api_url = $this->server_url;

        $h_parameters = [];

        if (empty($this->query_name)) {
            $api_url .= $query;
        } else {
            $h_parameters[$this->query_name] = $query;
        }

        foreach($this->auth as $auth_name => $auth_par) {
            if (!\is_null($auth_par) && !empty($auth_name)) {
                $h_parameters[$auth_name] = $auth_par;
            }
        }

        $response_data = $this->doApiCall(
            $api_url, $h_parameters, $is_post, $post_data, $file, $put
        );

        return ($response_data['code'] === 102) ? $this : $response_data;
    }

    /**
     * Function for send API query to server and get answer
     *
     * @param string|false $api_url Full URL of server API (false => $this->server_url)
     * @param array $h_params Parameters for adding after "?"
     * @param boolean $post_mode true for POST request, false for GET request
     * @param array|string|null $post_data Data for send in body of POST-request
     * @param string|null $file file name (full name with path) for send
     * @return array|resource
     */
    public function doApiCall($api_url,
        $h_params,
        $post_mode = false,
        $post_data = null,
        $file = null,
        $put = false
    ) {
        $yi = $this->yiDoApiCall($api_url, $h_params, $post_mode, $post_data, $file, $put);
        $curl_h = $yi->current();
        if (empty($this->to_slot)) {
            $response = \curl_exec($curl_h);
            $curl_error = \curl_error($curl_h);
            $curl_info = [];
            foreach (\array_keys($this->curl_info) as $key) {
                $curl_info[$key] = \curl_getinfo($curl_h, $key);
            }
            $this->curl_info = $curl_info;
            \curl_close($curl_h);
            $response_arr = $yi->send(\compact('response', 'curl_error', 'curl_info'));
            if ($this->debug) {
                $yi->next();
            }
        } else {
            $response_arr = $this->slotStart($this->to_slot, $curl_h,
                ['mode' => 1, 'fn' => $yi, 'par' => 'doApiCall']);
        }
        return $response_arr;
    }

    public function yiDoApiCall($api_url,
        $h_params,
        $post_mode = false,
        $post_data = null,
        $file = null,
        $put_mode = false
    ) {
        if (empty($api_url)) {
            $api_url = $this->server_url;
        }
        $api_url .= "?" . \http_build_query($h_params);

        if ($this->hook_before_api_call) {
            $api_url = call_user_func($this->hook_before_api_call, $api_url, $this);
        }

        if ($this->debug) {
            echo ($post_mode ? 'POST' : 'GET') . "->$api_url\n" . $file;
        }

        $curl_h = \curl_init($api_url);

        if ($post_mode) {
            if (empty($post_data)) {
                $post_data = array();
            }

            if (!empty($file) && \file_exists($file)) {
                if ($put_mode) {
                    $this->fh = \fopen($file, 'rb');
                    if (substr($file, -3) !== '.gz') {
                        if ($this->is_windows) {
                            // if Windows, compress file before sending
                            $dest = $file . '.gz';
                            if ($fp_out = \gzopen($dest, 'wb')) {
                                while (!\feof($this->fh)) {
                                    \gzwrite($fp_out, fread($this->fh, 524288));
                                }
                                \fclose($this->fh);
                                \gzclose($fp_out);
                                $this->fh = \fopen($dest, 'rb');
                            } else {
                                throw new \Exception("Can't read source file $file");
                            }
                        } else {
                            // if not Windows - can gzip on the fly
                            \stream_filter_append($this->fh, 'zlib.deflate',
                                \STREAM_FILTER_READ, ["window" => 30]);
                        }
                    }

                    \curl_setopt($curl_h, \CURLOPT_HTTPHEADER, [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Content-Encoding: gzip',
                        'Expect:',
                        ]);

                    \curl_setopt($curl_h, \CURLOPT_SAFE_UPLOAD, 1);
                    \curl_setopt($curl_h, \CURLOPT_PUT, true);
                    \curl_setopt($curl_h, \CURLOPT_INFILE, $this->fh);
                } else {
                    $post_data['file'] = \curl_file_create($file);
                }
            }
            \curl_setopt($curl_h, \CURLOPT_POST, true);
            \curl_setopt($curl_h, \CURLOPT_POSTFIELDS, $post_data);
        }
        \curl_setopt_array($curl_h, $this->curl_options);

        $response_arr = (yield $curl_h);
        \extract($response_arr); // $response, $curl_error, $curl_info

        $this->last_curl_error_str = $curl_error;
        $this->last_code = $code = $curl_info[\CURLINFO_HTTP_CODE];

        yield \compact('code', 'curl_error', 'response');
        echo "HTTP $code $curl_error \n\n$response\n}\n";
    }
}
