<?php
namespace Poirot\Http;

use Poirot\Core\ObjectCollection;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;

class Headers extends ObjectCollection
    implements iHeaderCollection
{
    /**
     * Attach Object
     *
     * - replace object with new data if exists
     *
     * note: recommend that object index by Unified ETag
     *       for better search and performance
     *
     * @param object $object
     * @param array $data    associate array that it can be used to attach some data
     *                       this data can be available for some codes
     *                       block that need this data ...
     *                       in case of render, view renderer can match
     *                       headers that attached by itself and make
     *                       some condition.
     *
     * @throws \InvalidArgumentException Object Type Mismatch
     * @return string ETag Hash Identifier of object
     */
    function attach($object, array $data = [])
    {
        /** @var iHeader $object */
        $data['label'] = $object->getLabel();

        return parent::attach($object, $data);
    }

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
