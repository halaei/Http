<?php
namespace Poirot\Http 
{

    use Poirot\Http\Interfaces\iHttpRequest;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;

    /**
     * Parse Http Request Message To It's Parts
     * @param string $message
     * @return array
     */
    function parseRequestFromString($message)
    {
        if (!preg_match_all('/.*[\n]?/', $message, $lines))
            throw new \InvalidArgumentException('Error Parsing Request Message.');

        $Return = array();

        $lines = $lines[0];

        // request line:
        $firstLine = array_shift($lines);
        $matches = null;
        $methods = implode('|', array(
            iHttpRequest::METHOD_OPTIONS, iHttpRequest::METHOD_GET, iHttpRequest::METHOD_HEAD, iHttpRequest::METHOD_POST,
            iHttpRequest::METHOD_PUT, iHttpRequest::METHOD_DELETE, iHttpRequest::METHOD_TRACE, iHttpRequest::METHOD_CONNECT,
            iHttpRequest::METHOD_PATCH
        ));
        $regex     = '#^(?P<method>' . $methods . ')\s(?P<uri>[^ ]*)(?:\sHTTP\/(?P<version>\d+\.\d+)){0,1}#';
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid request line was not found in the provided message.'
            );

        $Return['method'] = $matches['method'];
        $Return['uri']    = $matches['uri'];

        (!isset($matches['version']))
            ?: $Return['version'] = $matches['version'];

        // headers:
        $Return['headers'] = array();
        while ($nextLine = array_shift($lines)) {
            if (trim($nextLine) == '')
                ## headers end
                break;

            $ph = \Poirot\Http\Header\parseLabelValue($nextLine);
            $Return['headers'][key($ph)] = current($ph);
        }

        // body:
        $Return['body'] = rtrim(implode("\r\n", $lines), "\r\n");

        return $Return;
    }

    /**
     * Parse Psr Http Message To It's Parts
     * @param RequestInterface $psrRequest
     * @return array
     */
    function parseRequestFromPsr(RequestInterface $psrRequest)
    {
        $headers = array();
        foreach($psrRequest->getHeaders() as $h => $v)
            $headers[$h] = $v;

        $Return = array(
            'method'  => $psrRequest->getMethod(),
            'uri'     => $psrRequest->getUri(),
            'version' => $psrRequest->getProtocolVersion(),
            'headers' => $headers,
            'body'    => $psrRequest->getBody(),
        );

        return $Return;
    }

    /**
     * Parse Http Response Message To It's Parts
     * @param string $message
     * @return array
     */
    function parseResponseFromString($message)
    {
        if (!preg_match_all('/.*[\r\n]?/', $message, $lines))
            throw new \InvalidArgumentException('Error Parsing Response Message.');

        $Return = array();
        
        $lines = $lines[0];

        $regex     = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
        $firstLine = array_shift($lines);
        $matches   = array();
        if (!preg_match($regex, $firstLine, $matches))
            throw new \InvalidArgumentException(
                'A valid response status line was not found in the provided string.'
                . ' response:'
                . $message
            );

        $Return['version']       = $matches['version'];
        $Return['status_code']   = $matches['status'];
        $Return['status_reason'] = ( (isset($matches['reason']) ? $matches['reason'] : '') );

        // headers:
        $Return['headers'] = array();
        while ($nextLine = array_shift($lines)) {
            if (trim($nextLine) == '')
                // headers end
                break;

            $ph = \Poirot\Http\Header\parseLabelValue($nextLine);
            $Return['headers'][key($ph)] = current($ph);
        }

        // body:
        $Return['body'] = (rtrim(implode("\r\n", $lines), "\r\n"));

        return $this;
    }

    /**
     * Parse Psr Http Message To It's Parts
     * @param ResponseInterface $psrResponse
     * @return array
     */
    function parseResponseFromPsr(ResponseInterface $psrResponse)
    {
        $headers = array();
        foreach($psrResponse->getHeaders() as $h => $v)
            $headers[$h] = \Poirot\Http\Header\joinParams($v);

        $Return = array(
            'version'     => $psrResponse->getProtocolVersion(),
            'stat_code'   => $psrResponse->getStatusCode(),
            'stat_reason' => $psrResponse->getReasonPhrase(),
            'headers'     => $headers,
            'body'        => $psrResponse->getBody(),
        );

        return $Return;
    }
}

namespace Poirot\Http\Psr 
{
    use Psr\Http\Message\MessageInterface;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\UploadedFileInterface;

    /**
     * String representation of an HTTP message.
     *
     * @param MessageInterface $httpMessage
     *
     * @return string
     */
    function messageToString(MessageInterface $httpMessage)
    {
        if ($httpMessage instanceof RequestInterface) {
            $msg = trim($httpMessage->getMethod() . ' '
                    . $httpMessage->getRequestTarget())
                . ' HTTP/' . $httpMessage->getProtocolVersion();
            if (!$httpMessage->hasHeader('host'))
                $msg .= "\r\nHost: " . $httpMessage->getUri()->getHost();
        } elseif ($httpMessage instanceof ResponseInterface) {
            $msg = 'HTTP/' . $httpMessage->getProtocolVersion() . ' '
                . $httpMessage->getStatusCode() . ' '
                . $httpMessage->getReasonPhrase();
        } else
            throw new \InvalidArgumentException('Unknown message type');

        foreach ($httpMessage->getHeaders() as $name => $values)
            $msg .= "\r\n{$name}: " . implode(', ', $values);

        return "{$msg}\r\n\r\n" . $httpMessage->getBody();
    }

    /**
     * Normalize uploaded files
     *
     * Transforms each value into an UploadedFileInterface instance, and ensures
     * that nested arrays are normalized.
     *
     * @param array $files
     * @return array
     *
     * @throws \InvalidArgumentException for unrecognized values
     */
    function normalizeFiles(array $files, $stream = null)
    {
        $normalized = array();
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = __createUploadedFileFromSpec($value, $stream);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = normalizeFiles($value, $stream);
                continue;
            }

            throw new \InvalidArgumentException('Invalid value in files specification');
        }

        return $normalized;
    }


    // ...

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    function __createUploadedFileFromSpec(array $value, $stream)
    {
        if (is_array($value['tmp_name']))
            return __normalizeNestedFileSpec($value, $stream);
    
        ($value === null) ?: $value['default_stream_class'] = $stream;
        return new UploadedFile($value);
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files
     * @return UploadedFileInterface[]
     */
    function __normalizeNestedFileSpec(array $files, $stream)
    {
        $files = array();
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = array(
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            );
            $files[$key] = __createUploadedFileFromSpec($spec, $stream);
        }
    
        return $files;
    }
}

namespace Poirot\Http\Cookie 
{
    function parseCookie($header)
    {
        $cookies = array();

        $cookie = new cookie();

        $parts = explode("=",$header);
        for ($i=0; $i< count($parts); $i++) {
            $key = null;
            $part = $parts[$i];
            if ($i==0) {
                $key = $part;
                continue;
            } elseif ($i== count($parts)-1) {
                $cookie->set_value($key,$part);
                $cookies[] = $cookie;
                continue;
            }
            $comps = explode(" ",$part);
            $new_key = $comps[count($comps)-1];
            $value = substr($part,0,strlen($part)-strlen($new_key)-1);
            $terminator = substr($value,-1);
            $value = substr($value,0,strlen($value)-1);
            $cookie->set_value($key,$value);
            if ($terminator == ",") {
                $cookies[] = $cookie;
                $cookie = new cookie();
            }

            $key = $new_key;
        }

        return $cookies;
    }

    class cookie {
        public $name = "";
        public $value = "";
        public $expires = "";
        public $domain = "";
        public $path = "";
        public $secure = false;

        public function set_value($key,$value) {
            switch (strtolower($key)) {
                case "expires":
                    $this->expires = $value;
                    return;
                case "domain":
                    $this->domain = $value;
                    return;
                case "path":
                    $this->path = $value;
                    return;
                case "secure":
                    $this->secure = ($value == true);
                    return;
            }
            if ($this->name == "" && $this->value == "") {
                $this->name = $key;
                $this->value = $value;
            }
        }
    }
}

namespace Poirot\Http\Header 
{
    /**
     * Filter a header value
     *
     * Ensures CRLF header injection vectors are filtered.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * This method filters any values not allowed from the string, and is
     * lossy.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return string
     */
    function filterValue($value)
    {
        $value  = (string) $value;
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = @ord($value[$i + 1]);
                $ws = @ord($value[$i + 2]);
                if ($lf === 10 && in_array($ws, [9, 32], true)) {
                    $string .= $value[$i] . $value[$i + 1];
                    $i += 1;
                }

                continue;
            }

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                continue;
            }

            $string .= $value[$i];
        }

        return trim($string);
    }

    /**
     * Validate a header value.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     * @param string $value
     * @return bool
     */
    function isValidValue($value)
    {
        $value  = (string) $value;

        // Look for:
        // \n not preceded by \r, OR
        // \r not followed by \n, OR
        // \r\n not followed by space or horizontal tab; these are all CRLF attacks
        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value))
            // TODO with parsed headers \r\n not available here
            VOID;//return false;

        $length = strlen($value);
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 10 === line feed
            // 13 === carriage return
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (($ascii < 32 && ! in_array($ascii, [9, 10, 13], true))
                || $ascii === 127
                || $ascii > 254
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse Header line
     *
     * @param string $line
     *
     * @return false|array[string 'label', string 'value']
     */
    function parseLabelValue($line)
    {
        if (! preg_match('/^(?P<label>[^()><@,;:\"\\/\[\]?=}{ \t]+):(?P<value>.*)$/', $line, $matches))
            return false;

        return array( $matches['label'] => $matches['value'] );
    }
    
    function parseHeaderLines($headers)
    {
        if (!preg_match_all('/.*[\n]?/', $headers, $lines))
            throw new \InvalidArgumentException('Error Parsing Request Message.');

        $headers = array();
        foreach ($lines[0] as $l) {
            // Todo parse lines have empty string at the end
            if (empty($l)) continue;
            if (( $h = parseLabelValue($l) ) === false)
                throw new \Exception(sprintf(
                    'Malformed Header; (%s).'
                    , $h
                ));

            $headers[key($h)] = current($h);
        }
        
        return $headers;
    }

    /**
     * TODO test more
     * TODO Basic r4rewrwerr3r= with join result in Basic; r4rewrwerr3r=; that is wrong
     *
     * This function is useful for parsing header fields that
     * follow this syntax (BNF as from the HTTP/1.1 specification, but we relax
     * the requirement for tokens).
     *
     * Each header is represented by an anonymous array of key/value
     * pairs. The value for a simple token (not part of a parameter) is null.
     * Syntactically incorrect headers will not necessary be parsed as you
     * would want.
     *
     * This is easier to describe with some examples:
     *
     * headerParseParams('foo="bar"; port="80,81"; discard, bar=baz');
     * headerParseParams('text/html; charset="iso-8859-1");
     * headerParseParams('Basic realm="\"foo\\bar\""');
     *
     * will return
     *
     * [foo=>'bar', port=>'80,81', discard=>null], [bar=>'baz']
     * ['text/html'=>null, charset=>'iso-8859-1']
     * [Basic=>null, realm=>'"foo\bar"']
     *
     * @param mixed $header_values string or array
     * @return array
     * @static
     */
    function parseParams($header_values)
    {
        if (!is_array($header_values)) $header_values = [$header_values];

        $result = array();
        foreach ($header_values as $header) {
            $cur = array();
            while ($header) {
                $key = '';
                $val = null;
                // 'token' or parameter 'attribute'
                if (preg_match('/^\s*(=*[^\s=;,]+)(.*)/', $header, $match)) {
                    $key = $match[1];
                    $header = $match[2];
                    // a quoted value
                    if (preg_match('/^\s*=\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(.*)/', $header, $match)) {
                        $val = $match[1];
                        $header = $match[2];
                        // remove backslash character escape
                        $val = preg_replace('/\\\\(.)/', "$1", $val);
                        // some unquoted value
                    } elseif (preg_match('/^\s*=\s*([^;,\s]*)(.*)/', $header, $match)) {
                        $val = trim($match[1]);
                        $header = $match[2];
                    }
                    // add details
                    $cur[$key] = $val;
                    // reached the end, a new 'token' or 'attribute' about to start
                } elseif (preg_match('/^\s*,(.*)/', $header, $match)) {
                    $header = $match[1];
                    if (count($cur)) $result[] = $cur;
                    $cur = array();
                    // continue
                } elseif (preg_match('/^\s*;(.*)/', $header, $match)) {
                    $header = $match[1];
                } elseif (preg_match('/^\s+(.*)/', $header, $match)) {
                    $header = $match[1];
                } else {
                    return $result;
                }
            }
            if (count($cur)) $result[] = $cur;
        }
        return $result;
    }

    /**
     * TODO test more
     * TODO Basic r4rewrwerr3r= with join result in Basic; r4rewrwerr3r=; that is wrong
     *
     * This will do the opposite of the conversion done by headerParseParams().
     * It takes a list of anonymous arrays as arguments (or a list of
     * key/value pairs) and produces a single header value. Attribute values
     * are quoted if needed.
     *
     * Example:
     *
     * headerJoinParams(array(array("text/plain" => null, "charset" => "iso-8859/1")));
     * headerJoinParams(array("text/plain" => null, "charset" => "iso-8859/1"));
     *
     * will both return the string:
     *
     * text/plain; charset="iso-8859/1"
     *
     * @param array $header_values
     * @return string
     * @static
     */
    function joinParams($header_values)
    {
        if (!is_array($header_values) || !count($header_values)) return false;
        if (!isset($header_values[0])) $header_values = array($header_values);

        $result = array();
        foreach ($header_values as $header) {
            $attr = array();
            foreach ($header as $key => $val) {
                if (isset($val)) {
                    if (preg_match('/^\w+$/', $val)) {
                        $key .= "=$val";
                    } else {
                        $val = preg_replace('/(["\\\\])/', "\\\\$1", $val);
                        $key .= "=\"$val\"";
                    }
                }
                $attr[] = $key;
            }
            if (count($attr)) $result[] = implode('; ', $attr);
        }
        return implode(', ', $result);
    }
}

namespace Poirot\Http\Response 
{
    /**
     * Map of standard HTTP status code/reason phrases
     *
     * @var array
     */
    $phrases = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    /**
     * Get Status Code Reason
     *
     * @param int $statusCode
     *
     * @return null|string
     */
    function getStatReasonFromCode($statusCode)
    {
        global $phrases;
        return isset($phrases[$statusCode]) ? $phrases[$statusCode] : null;
    }

    /**
     * Get or Set the HTTP response code
     *
     * @param int $statusCode
     *
     * @return int
     */
    function httpResponseCode($statusCode = null)
    {
        // TODO @link http://stackoverflow.com/questions/3258634/php-how-to-send-http-response-code
        if (function_exists('http_response_code'))
            return http_response_code($statusCode);


        // ...

        static $_c_code;
        if ($_c_code == null)
            $_c_code = 200;

        if ($statusCode !== null) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $_c_code . ' ' . getStatReasonFromCode($_c_code));
            $_c_code = $statusCode;
        }

        return $_c_code;
    }
}

namespace Poirot\Http\Mime 
{
    /**
     * Determines the mimetype of a file by looking at its extension.
     *
     * @param $filename
     *
     * @return null|string
     */
    function getFromFilename($filename)
    {
        return getFromExtension(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Maps a file extensions to a mimetype.
     *
     * @param $ext string The file extension.
     *
     * @return string|null
     * @link http://svn.apache.org/repos/asf/httpd/httpd/branches/1.3.x/conf/mime.types
     */
    function getFromExtension($ext)
    {
        static $mimetypes = array(
            '7z' => 'application/x-7z-compressed',
            'aac' => 'audio/x-aac',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'asc' => 'text/plain',
            'asf' => 'video/x-ms-asf',
            'atom' => 'application/atom+xml',
            'avi' => 'video/x-msvideo',
            'bmp' => 'image/bmp',
            'bz2' => 'application/x-bzip2',
            'cer' => 'application/pkix-cert',
            'crl' => 'application/pkix-crl',
            'crt' => 'application/x-x509-ca-cert',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'cu' => 'application/cu-seeme',
            'deb' => 'application/x-debian-package',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dvi' => 'application/x-dvi',
            'eot' => 'application/vnd.ms-fontobject',
            'eps' => 'application/postscript',
            'epub' => 'application/epub+zip',
            'etx' => 'text/x-setext',
            'flac' => 'audio/flac',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'gz' => 'application/gzip',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'ini' => 'text/plain',
            'iso' => 'application/x-iso9660-image',
            'jar' => 'application/java-archive',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'text/javascript',
            'json' => 'application/json',
            'latex' => 'application/x-latex',
            'log' => 'text/plain',
            'm4a' => 'audio/mp4',
            'm4v' => 'video/mp4',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'mp4' => 'video/mp4',
            'mp4a' => 'audio/mp4',
            'mp4v' => 'video/mp4',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpg4' => 'video/mp4',
            'oga' => 'audio/ogg',
            'ogg' => 'audio/ogg',
            'ogv' => 'video/ogg',
            'ogx' => 'application/ogg',
            'pbm' => 'image/x-portable-bitmap',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'rar' => 'application/x-rar-compressed',
            'ras' => 'image/x-cmu-raster',
            'rss' => 'application/rss+xml',
            'rtf' => 'application/rtf',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'svg' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tar' => 'application/x-tar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'torrent' => 'application/x-bittorrent',
            'ttf' => 'application/x-font-ttf',
            'txt' => 'text/plain',
            'wav' => 'audio/x-wav',
            'webm' => 'video/webm',
            'wma' => 'audio/x-ms-wma',
            'wmv' => 'video/x-ms-wmv',
            'woff' => 'application/x-font-woff',
            'wsdl' => 'application/wsdl+xml',
            'xbm' => 'image/x-xbitmap',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-xwindowdump',
            'yaml' => 'text/yaml',
            'yml' => 'text/yaml',
            'zip' => 'application/zip',
        );

        $ext = strtolower($ext);

        return isset($mimetypes[$ext])
            ? $mimetypes[$ext]
            : null;
    }
}
