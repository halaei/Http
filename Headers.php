<?php
namespace Poirot\Http;

use Poirot\Core\ObjectCollection;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;

class Headers extends ObjectCollection
    implements iHeaderCollection
{
    /**
     * @param $object
     *
     * @throws \InvalidArgumentException
     */
    protected function _validateObject($object)
    {
        if (!is_object($object) && $object instanceof iHeader)
            throw new \InvalidArgumentException(sprintf(
                'Object must be an interface of iHeader, "%s" given.'
                , is_object($object) ? get_class($object) : gettype($object)
            ));
    }
}
