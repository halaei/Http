<?php
namespace Poirot\Http\Psr;

use Psr\Http\Message\StreamInterface;

class Stream
    implements StreamInterface
{
    /** @var resource */
    protected $rHandler;

    /** @var StreamInterface */
    protected $_stream_cache;

    /** @var array Hash of readable and writable stream types */
    private static $readWriteHash = array(
        'read' => array(
            'r',   'w+',  'r+',  'x+', 'c+',
            'rb',  'w+b', 'r+b', 'x+b',
            'c+b', 'rt',  'w+t', 'r+t',
            'x+t', 'c+t', 'a+',
        ),
        'write' => array(
            'w',   'w+',  'rw',  'r+', 'x+',
            'c+',  'wb',  'w+b', 'r+b',
            'x+b', 'c+b', 'w+t', 'r+t',
            'x+t', 'c+t', 'a',   'a+',
        )
    );

    
    /**
     * Construct
     *
     * @param string|resource $stream
     * @param string          $mode   Mode with which to open stream
     *
     * @throws \InvalidArgumentException
     */
    function __construct($stream, $mode = 'br')
    {
        $resource = $stream;

        if (is_string($stream)) {
            if( false === ($resource = fopen($stream, $mode)) )
                throw new \RuntimeException(sprintf(
                    'Error while trying to connect to (%s).'
                    , $resource
                ));
        }

        if ( !is_resource($resource) )
            throw new \InvalidArgumentException(sprintf(
                'Invalid stream provided; must be a string stream identifier or resource.'
                . ' given: "%s"'
                , \Poirot\Std\flatten($resource)
            ));


        $this->rHandler = $resource;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    function __toString()
    {
        try {
            (!$this->isSeekable()) ?: $this->seek(0);
            return $this->getContents();
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    function close()
    {
        if (is_resource($this->rHandler))
            fclose($this->rHandler);

        $this->detach();
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    function detach()
    {
        // cache stream
        if ($cStream = $this->_attainCacheStream())
            $cStream->close();
        
        $this->_stream_cache = null;
        $this->rHandler      = null;
    }
    
    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    function getSize()
    {
        $size  = null;
        $cSize = null;
        
        # cache stream
        if ($this->_attainCacheStream())
            $cSize = $this->_attainCacheStream()->getSize();
        
        # main stream size
        if ($this->_assertStreamAlive()) {
            if ($uri = $this->getMetadata('uri'))
                ## clear the stat cache of stream URI
                clearstatcache(true, $uri);

            // TODO can't achieve size of php input stream
            $stats = fstat($this->rHandler);
            if (isset($stats['size']))
                $size = $stats['size'];
        }

        return max($cSize, $size);
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    function tell()
    {
        # cache stream
        if ($stream = $this->_attainCacheStream())
            return $stream->tell();
        
        # main stream
        if (!$this->_assertStreamAlive())
            throw new \RuntimeException('No resource available; cannot tell position');

        if (false === $r = ftell($this->rHandler))
            throw new \RuntimeException('Unable to determine stream position');

        return $r;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    function eof()
    {
        # cache stream
        if ($stream = $this->_attainCacheStream())
            return $stream->eof();

        # main stream
        return ( !$this->_assertStreamAlive() || feof($this->rHandler) );
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    function isSeekable()
    {
        # cache stream
        if ($stream = $this->_attainCacheStream())
            return $stream->isSeekable();
        
        # main stream
        if (!$this->_assertStreamAlive())
            return false;

        return $this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->_assertStreamAlive())
            throw new \RuntimeException('No resource available; cannot seek position');

        if ($whence == SEEK_SET) {
            $byte = $offset;
        } elseif ($whence == SEEK_CUR) {
            $byte = $offset + $this->tell();
        } elseif ($whence == SEEK_END) {
            $size = $this->_attainCacheStream()->getSize();
            if ($size === null) {
                $size = $this->cacheEntireStream();
            }
            $byte = $size + $offset;
        } else {
            throw new \InvalidArgumentException('Invalid whence');
        }

        $diff = $byte - $this->stream->getSize();
        if ($diff > 0) {
            // Read the remoteStream until we have read in at least the amount
            // of bytes requested, or we reach the end of the file.
            while ($diff > 0 && !$this->remoteStream->eof()) {
                $this->read($diff);
                $diff = $byte - $this->stream->getSize();
            }
        } else {
            // We can just do a normal seek since we've already seen this byte.
            $this->stream->seek($byte);
        }
        
        if (-1 === fseek($this->rHandler, $offset, $whence))
            throw new \RuntimeException('Cannot seek on stream');
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    function isWritable()
    {
        if (!$this->_assertStreamAlive())
            return false;

        $mode = $this->getMetadata('mode');
        return in_array($mode, self::$readWriteHash['write']);
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    function write($string)
    {
        if (!$this->_assertStreamAlive())
            throw new \RuntimeException('No resource available; cannot write');

        if (!$this->isWritable())
            throw new \RuntimeException('resource is not writable.');

        $content = (string) $string;
        $result  = fwrite($this->rHandler, $content);

        if (false === $result)
            throw new \RuntimeException('Cannot write on stream.');

        $transCount = mb_strlen($content, '8bit');
        return $transCount;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    function isReadable()
    {
        if (!$this->_assertStreamAlive())
            return false;

        $mode = $this->getMetadata('mode');
        return in_array($mode, self::$readWriteHash['read']);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if
     *                    underlying stream call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string
     *                if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    function read($length)
    {
        if (!$this->_assertStreamAlive())
            throw new \RuntimeException('No resource alive; cannot read.');

        if (!$this->isReadable())
            throw new \RuntimeException('resource is not readable.');

        $data   = stream_get_contents($this->rHandler, $length);
        if (false === $data)
            throw new \RuntimeException('Cannot read stream.');

        return $data;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *                           reading.
     */
    function getContents()
    {
        if (!$this->isReadable())
            throw new \RuntimeException('Stream is not readable.');

        $contents = $this->_streamGetContents();
        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    function getMetadata($key = null)
    {
        if (!$this->_assertStreamAlive())
            return ($key === null) ? array() : null;


        $meta = stream_get_meta_data($this->rHandler);

        if ($key === null)
            return $meta;

        return (array_key_exists($key, $meta)) ? $meta[$key] : null;
    }


    // ..

    /**
     * Attain Cache Stream
     *
     * - cache stream take an action when main stream
     *   is not seekable. (read once)
     * - cache stream is rw/seekable
     *
     * @return StreamInterface|false
     */
    function _attainCacheStream()
    {
        if (!is_resource($this->rHandler) || $this->getMetadata('seekable'))
            ## when resource is closed or isSeekable it's fine to have no cache main stream
            return false;

        if (!$this->_stream_cache)
            $this->_stream_cache = new Stream(fopen('php://temp', 'r+'));

        return $this->_stream_cache;
    }
    
    /**
     * @param int $maxLen
     * @return string|false
     */
    protected function _streamGetContents($maxLen = -1)
    {
        $buffer = '';

        if ($maxLen === -1) {
            while (!$this->eof()) {
                $buf = $this->read(1048576);
                // Using a loose equality here to match on '' and false.
                if ($buf == null) {
                    break;
                }
                $buffer .= $buf;
            }
            return $buffer;
        }

        $len = 0;
        while (!$this->eof() && $len < $maxLen) {
            $buf = $this->read($maxLen - $len);
            // Using a loose equality here to match on '' and false.
            if ($buf == null) {
                break;
            }
            $buffer .= $buf;
            $len = strlen($buffer);
        }

        return $buffer;
    }

    protected function _assertStreamAlive()
    {
        if (null === $this->rHandler || !is_resource($this->rHandler))
            return false;

        return true;
    }

    /**
     * Closes the stream when the destructed
     */
    function x__destruct()
    {
        $this->close();
    }
}
