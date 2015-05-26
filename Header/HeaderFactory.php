<?php
namespace Poirot\Http\Header;

/**
 * Implement Plugins
 */
class HeaderFactory
{
    protected static $headerAsParser;

    static function fromString($headerLine)
    {
        $parsed = self::_getHeaderParser()->parseHeader($headerLine);

        // Looking for header plugin parsed[label]:
        // TODO service plugin
        // $headerPlugin->fromString($headerLine);
        // return header

        return self::factory($parsed['label'], $parsed['value']);
    }

    static function factory($label, $value)
    {
        return new HeaderLine(['label' => $label, 'header_line' => $value]);
    }

    protected static function _getHeaderParser()
    {
        if (!self::$headerAsParser)
            self::$headerAsParser = new HeaderLine;

        return self::$headerAsParser;
    }
}
