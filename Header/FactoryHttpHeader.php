<?php
namespace Poirot\Http\Header;

/*
HeaderFactory::of('WWW-Authenticate: Basic realm="admin_panel"');
HeaderFactory::of(['WWW-Authenticate', 'Basic realm="admin_panel"']);
// options of specific header as plugin
HeaderFactory::of('WWW-Authenticate' => ['header_line' => 'Basic realm="admin_panel"']);
*/

use Poirot\Std\Interfaces\Pact\ipFactory;

class FactoryHttpHeader
    implements ipFactory
{
    /** @var PluginsHttpHeader */
    static protected $pluginManager;

    /**
     * Factory With Valuable Parameter
     *
     * @param array|string $valuable
     * array:
     *  ["label", "value"], ['label'=>'value']
     *
     * @throws \Exception
     * @return mixed
     */
    static function of($valuable)
    {
        // string:
        if (\Poirot\Std\isStringify($valuable)) {
            ## extract label and value from header
            $parsed = \Poirot\Http\Header\splitLabelValue( (string) $valuable);
            if ($parsed === false)
                throw new \InvalidArgumentException(sprintf(
                    'Invalid Header (%s)'
                    , $valuable
                ));

            return self::of( array(key($parsed) => current($parsed)) );
        }

        // array:
        if (!is_array($valuable) || (count($valuable) < 2 && array_values($valuable) === $valuable) )
            throw new \InvalidArgumentException(sprintf(
                'Header must be valid string or array[$label, $value] or ["label"=>$value]; given (%s).'
                , \Poirot\Std\flatten($valuable)
            ));

        if (count($valuable) >= 2) {
            ## [$label, $value, $other_value[] ]
            $label = array_shift($valuable);
            $value = $valuable;
        } else {
            ## ['label' => $value| $values[] ]
            $label = key($valuable);
            $value = current($valuable);
        }

        if (self::isEnabledPlugins() && self::plugins()->has($label))
            $header = self::plugins()->get($label);
        else
            $header = new HeaderLine;

        # Check Value
        if (!(is_array($value) || $value instanceof \Traversable)) {
            (is_string($value)) ?: $value = \Poirot\Std\toStrVar($value);

            if ($header instanceof HeaderLine)
                $header->setValueLine($value);

            $value = \Poirot\Http\Header\parseParams($value);
        }


        $header->import($value);
        $header->setLabel($label);
        return $header;
    }
    
    
    // ..

    /**
     * Is Enabled Plugins?
     *
     * @return bool
     */
    static function isEnabledPlugins()
    {
        return class_exists('Poirot\Ioc\Container\aContainerCapped');
    }

    /**
     * Headers Plugin Manager
     *
     * @return PluginsHttpHeader
     */
    static function plugins()
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
