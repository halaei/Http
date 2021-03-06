<?php
namespace Poirot\Http;

use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Std\Struct\ObjectCollection;
use Traversable;

class Headers
    implements iHeaderCollection
    , \IteratorAggregate # implement \Traversable
{
    /** @var ObjectCollection */
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
    function __construct(array $headers = [])
    {
        $this->ObjectCollection = new ObjectCollection;

        foreach ($headers as $label => $h) {
            if (!$h instanceof iHeader)
                // Header-Label: value header
                $h = HeaderFactory::factory($label, $h);

            $this->set($h);
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
     */
    function set(iHeader $header)
    {
        $search = ['label' => strtolower($header->getLabel())];

        /*foreach($this->ObjectCollection->search($search) as $h)
            ## previously values must overwrite
            $this->ObjectCollection->detach($h);*/

        $this->ObjectCollection->insert($header, $search);

        return $this;
    }

    /**
     * Get Header With Label
     *
     * ! headers label are case-insensitive
     *
     * @param string $label Header Label
     *
     * @throws \Exception header not found
     * @return iHeader
     */
    function get($label)
    {
        $r = $this->ObjectCollection->find(['label' => strtolower($label)]);
        $r = current($r);

        if (!$r instanceof iHeader)
            throw new \Exception("Header ({$label}) not found.");

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
        $r = $this->ObjectCollection->find(['label' => strtolower($label)]);
        $r = current($r);

        return (boolean) $r;
    }

    /**
     * Delete a Header With Label Name
     *
     * @param string $label
     *
     * @return Headers
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


    function __clone()
    {
        $this->ObjectCollection = clone $this->ObjectCollection;
    }
}
