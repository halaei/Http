<?php
namespace Poirot\Http\Header;

use Traversable;

use Poirot\Std\Struct\CollectionObject;

use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;

// TODO headers with same name withAddedHeader PSR
// TODO prepare using of CollectionObject Within

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
        foreach ($headers as $label => $h) {
            if (!$h instanceof iHeader)
                // Header-Label: value header
                $h = FactoryHttpHeader::of( array($label => $h) );

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
        
        
        $this->getIterator()->insert($header, array('label'=> strtolower($header->getLabel())));
        return $this;
    }

    /**
     * Get Header With Label
     *
     * ! headers label are case-insensitive
     *
     * @param string $label Header Label
     *
     * @return \Generator|\Traversable[iHeader]
     * @throws \Exception header not found
     */
    function get($label)
    {
        $r = $this->getIterator()->find( array('label' => strtolower($label)) );
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
        $r = $this->getIterator()->find( array('label' => strtolower($label)) );
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

        $headers = $this->getIterator()->find( array('label' => strtolower($label)) );
        foreach ($headers as $header)
            foreach ($header as $hash => $object)
                $this->getIterator()->del($hash);
        
        return $this;
    }

    /**
     * Remove All Entities Item
     *
     * @return $this
     */
    function clean()
    {
        $this->getIterator()->clean();
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
        if (!$this->ObjectCollection)
            $this->ObjectCollection = new CollectionObject;

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
        return $this->getIterator()->count();
    }

    // ..

    function __clone()
    {
        if ($this->ObjectCollection)
            $this->ObjectCollection = null;
    }
}
