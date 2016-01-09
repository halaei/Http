<?php
namespace Poirot\Http\Util;

class UHeader
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
    static function filterValue($value)
    {
        $value  = (string) $value;
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = ord($value[$i + 1]);
                $ws = ord($value[$i + 2]);
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

        return $string;
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
    static function isValidValue($value)
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
    static function parseLabelValue($line)
    {
        if (! preg_match('/^(?P<label>[^()><@,;:\"\\/\[\]?=}{ \t]+):(?P<value>.*)$/', $line, $matches))
            return false;

        return [ $matches['label'] => $matches['value'] ];
    }

    /**
     * TODO test more
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
    static function parseParams($header_values)
    {
        if (!is_array($header_values)) $header_values = [$header_values];

        $result = [];
        foreach ($header_values as $header) {
            $cur = [];
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
    static function joinParams($header_values)
    {
        if (!is_array($header_values) || !count($header_values)) return false;
        if (!isset($header_values[0])) $header_values = array($header_values);

        $result = [];
        foreach ($header_values as $header) {
            $attr = [];
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