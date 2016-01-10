<?php
namespace Poirot\Http\Header;

use Poirot\Http\Util\UHeader;

/*
HeaderFactory::factoryString('WWW-Authenticate: Basic realm="admin_panel"');
HeaderFactory::factory('WWW-Authenticate', 'Basic realm="admin_panel"');
// options of specific header as plugin
HeaderFactory::factory('WWW-Authenticate', ['header_line' => 'Basic realm="admin_panel"']);
*/

class HeaderFactory
{
    /** @var HeaderPluginsManager */
    static protected $pluginManager;

    /**
     * Factory Header Object From String
     *
     * Header-Label: value, values;
     *
     * @param string $headerLine
     *
     * @return HeaderLine
     */
    static function factoryString($headerLine)
    {
        ## extract label and value from header
        $parsed = UHeader::parseLabelValue( (string) $headerLine);
        if ($parsed === false)
            throw new \InvalidArgumentException(sprintf(
                'Invalid Header (%s)'
                , $headerLine
            ));

        return self::factory(key($parsed), current($parsed));
    }

    /**
     * Factory Header Object
     *
     * @param string $label
     * @param mixed  $value
     *
     * @return HeaderLine
     */
    static function factory($label, $value)
    {
        if (self::getPluginManager()->has($label))
            $header = self::getPluginManager()->get($label);
        else
            $header = new HeaderLine;

        if (is_string($value))
            ## avoid to parse again header value
            $header->from($label.': '. $value);
        else {
            $header->from($value);
            $header->setLabel($label);
        }

        return $header;
    }

    /**
     * Headers Plugin Manager
     *
     * @return HeaderPluginsManager
     */
    static function getPluginManager()
    {
        if (!self::$pluginManager)
            self::$pluginManager = new HeaderPluginsManager;

        return self::$pluginManager;
    }

    /**
     * Set Headers Plugin Manager
     *
     * @param HeaderPluginsManager $pluginsManager
     */
    static function setPluginManager(HeaderPluginsManager $pluginsManager)
    {
        self::$pluginManager = $pluginsManager;
    }
}
