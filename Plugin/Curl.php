<?php

namespace Drop\HttpVersion\Plugin;

class Curl
{
    /**
     * Parameters array
     *
     * @var array
     */
    protected $_config = [
        'protocols'  => (CURLPROTO_HTTP
            | CURLPROTO_HTTPS
            | CURLPROTO_FTP
            | CURLPROTO_FTPS
        ),
        'verifypeer' => true,
        'verifyhost' => 2
    ];
    /**
     * Array of CURL optionsw//
     *
     * @var array
     */
    protected $_options = [];

    /**
     * Curl handle
     *
     * @var resource
     */
    protected $_resource;

    /**
     * Allow parameters
     *
     * @var array
     */
    protected $_allowedParams = [
        'timeout'      => CURLOPT_TIMEOUT,
        'maxredirects' => CURLOPT_MAXREDIRS,
        'proxy'        => CURLOPT_PROXY,
        'ssl_cert'     => CURLOPT_SSLCERT,
        'userpwd'      => CURLOPT_USERPWD,
        'useragent'    => CURLOPT_USERAGENT,
        'referer'      => CURLOPT_REFERER,
        'protocols'    => CURLOPT_PROTOCOLS,
        'verifypeer'   => CURLOPT_SSL_VERIFYPEER,
        'verifyhost'   => CURLOPT_SSL_VERIFYHOST,
        'sslversion'   => CURLOPT_SSLVERSION,
    ];

    /**
     * Apply current configuration array to transport resource
     *
     * @return \Drop\HttpVersion\Plugin\Curl
     */
    protected function _applyConfig()
    {
        // apply additional options to cURL
        foreach ($this->_options as $option => $value) {
            curl_setopt($this->_getResource(), $option, $value);
        }

        // apply config options
        foreach ($this->getDefaultConfig() as $option => $value) {
            curl_setopt($this->_getResource(), $option, $value);
        }

        return $this;
    }

    /**
     * Returns a cURL handle on success
     *
     * @return resource
     */
    protected function _getResource()
    {
        if ($this->_resource === null) {
            $this->_resource = curl_init();
        }
        return $this->_resource;
    }

    /**
     * Get default options
     *
     * @return array
     */
    private function getDefaultConfig()
    {
        $config = [];
        foreach (array_keys($this->_config) as $param) {
            if (array_key_exists($param, $this->_allowedParams)) {
                $config[$this->_allowedParams[$param]] = $this->_config[$param];
            }
        }
        return $config;
    }

    /**
     * @param \Magento\Framework\HTTP\Adapter\Curl $subject
     * @param callable $proceed
     * @param $method
     * @param $url
     * @param string $http_ver
     * @param array $headers
     * @param string $body
     * @return string
     */
    public function aroundWrite(\Magento\Framework\HTTP\Adapter\Curl $subject, callable $proceed, $method, $url, $http_ver = '1.1', $headers = [], $body = '')
    {
        if ($url instanceof \Zend_Uri_Http) {
            $url = $url->getUri();
        }
        $this->_applyConfig();

        // set url to post to
        curl_setopt($this->_getResource(), CURLOPT_URL, $url);
        curl_setopt($this->_getResource(), CURLOPT_RETURNTRANSFER, true);
        if ($method == \Zend_Http_Client::POST) {
            curl_setopt($this->_getResource(), CURLOPT_POST, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method == \Zend_Http_Client::PUT) {
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->_getResource(), CURLOPT_POSTFIELDS, $body);
        } elseif ($method == \Zend_Http_Client::GET) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPGET, true);
            curl_setopt($this->_getResource(), CURLOPT_CUSTOMREQUEST, 'GET');
        }

        // fix Zend_Http_Client HTTP version
        if ($http_ver === \Zend_Http_Client::HTTP_1) {
            curl_setopt($this->_getResource(), CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        } elseif ($http_ver === \Zend_Http_Client::HTTP_0) {
            curl_setopt($this->_getResource(), CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        }

        if (is_array($headers)) {
            curl_setopt($this->_getResource(), CURLOPT_HTTPHEADER, $headers);
        }

        /**
         * @internal Curl options setter have to be re-factored
         */
        $header = isset($this->_config['header']) ? $this->_config['header'] : true;
        curl_setopt($this->_getResource(), CURLOPT_HEADER, $header);

        return $body;
    }
}

