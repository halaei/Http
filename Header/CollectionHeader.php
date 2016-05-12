<?php
namespace Poirot\Http\Header;

use Poirot\Std\Struct\CollectionObject;
use Traversable;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;

class CollectionHeader
    implements iHeaders
    , \IteratorAggregate # implement \Traversable
{
    /** @var CollectionObject */
    protected $ObjectCollection;

    /**
     * Construct
     *
     * $headers:
     *   ['Header-Label' => 'value, values', ..]
     *   [iHeader, ..]
     *
     * @param array $headers
     */
    function __construct(array $headers = array())
    {
        $this->ObjectCollection = new CollectionObject;

        foreach ($headers as $label => $h) {
            if (!$h instanceof iHeader)
                // Header-Label: value header
                $h = FactoryHttpHeader::of( array($label, $h) );

            $this->insert($h);
        }
    }
    
    /**
     * Set Header
     *
     * ! headers label are case-insensitive
     *
     * @param iHeader $header
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    function insert($header)
    {
        if (!$header instanceof iHeader)
            throw new \InvalidArgumentException(sprintf(
                'Header must instance of iHeader; given: (%s).'
                , \Poirot\Std\flatten($header)
            ));
        
        $this->ObjectCollection->insert($header);
        return $this;
    }

    /**
     * Get Header With Label
     *
     * ! headers label are case-insensitive
     *
     * @param string $label Header Label
     *
     * @return \Traversable[iHeader]
     * @throws \Exception header not found
     */
    function get($label)
    {
        $r = $this->ObjectCollection->find( array('label' => strtolower($label)) );
        return $r;
    }

    /**
     * Has Header With Specific Label?
     *
     * ! headers label are case-insensitive
     *
     * @param string $label
     *
     * @return bool
     */
    function has($label)
    {
        $r = $this->ObjectCollection->find( array('label' => strtolower($label)) );
        foreach ($r as $v)
            return true;

        return false;
    }

    /**
     * Delete a Header With Label Name
     *
     * @param string $label
     *
     * @return CollectionHeader
     */
    function del($label)
    {
        if (!$this->has($label))
            return $this;

        // ..

        $header = $this->get($label);
        $this->ObjectCollection->del($header);
        return $this;
    }

    /**
     * Remove All Entities Item
     *
     * @return $this
     */
    function clean()
    {
        $this->ObjectCollection->clean();
    }

    
    // Implement Traversable

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        return $this->ObjectCollection;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->ObjectCollection->count();
    }

    // ..

    function __clone()
    {
        $this->ObjectCollection = clone $this->ObjectCollection;
    }
}
