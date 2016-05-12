<?php
namespace Poirot\Http\Header;

/*
HeaderFactory::factoryString('WWW-Authenticate: Basic realm="admin_panel"');
HeaderFactory::factory('WWW-Authenticate', 'Basic realm="admin_panel"');
// options of specific header as plugin
HeaderFactory::factory('WWW-Authenticate', ['header_line' => 'Basic realm="admin_panel"']);
*/

use Poirot\Std\Interfaces\Pact\ipFactory;

class factoryHttpHeader
    implements ipFactory
{
    /** @var PluginsHttpHeader */
    static protected $pluginManager;

    /**
     * Factory With Valuable Parameter
     *
     * @param mixed $valuable
     *
     * @throws \Exception
     * @return mixed
     */
    static function of($valuable)
    {
        // string:

        ## extract label and value from header
        $parsed = \Poirot\Http\Header\parseLabelValue( (string) $headerLine);
        if ($parsed === false)
            throw new \InvalidArgumentException(sprintf(
                'Invalid Header (%s)'
                , $headerLine
            ));

        return self::of(key($parsed), current($parsed));
        
        
        // array:
        
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
    
    
    // ..
    
    /**
     * Headers Plugin Manager
     *
     * @return PluginsHttpHeader
     */
    static function getPluginManager()
    {
        if (!self::$pluginManager)
            self::$pluginManager = new PluginsHttpHeader;

        return self::$pluginManager;
    }

    /**
     * Set Headers Plugin Manager
     *
     * @param PluginsHttpHeader $pluginsManager
     */
    static function setPluginManager(PluginsHttpHeader $pluginsManager)
    {
        self::$pluginManager = $pluginsManager;
    }
}
