<?php
namespace Poirot\Http\Header;

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

        return new HeaderLine($headerLine);
    }

    protected static function _getHeaderParser()
    {
        if (!self::$headerAsParser)
            self::$headerAsParser = new HeaderLine;

        return self::$headerAsParser;
    }
}
