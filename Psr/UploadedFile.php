<?php
namespace Poirot\Http\Psr;

use Poirot\Core\AbstractOptions;
use Poirot\Core\Traits\OptionsTrait;
use Poirot\Http\Psr\Interfaces\UploadedFileInterface;
use Poirot\Stream\Interfaces\iSResource;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\Psr\StreamPsr;
use Poirot\Stream\SResource;
use Poirot\Stream\WrapperClient;

class UploadedFile implements UploadedFileInterface
{
    use OptionsTrait;

    const DEFAULT_STREAM = '\Poirot\Stream\Psr\StreamPsr';

    /** @var null|string */
    protected $tmpName;
    /** @var int */
    protected $size;
    /** @var int */
    protected $error;
    /** @var string */
    protected $name;
    /** @var string */
    protected $type;


    protected $_t__stream;
    /** @var iStreamable|StreamInterface */
    protected $stream;

    /** @var string Default Stream Class */
    protected $defaultStreamClass;


    /** @var bool */
    protected $_hasMoved = false;


    /**
     * Construct
     *
     * fileValues: [
     *    'tmp_name' => '',
     *    |or| 'stream'   => iSResource|StreamInterface|iStreamable|resource,
     *    'size'     => '',
     *    'error'    => '',
     *    'name'     => '',
     *    'type'     => '',
     *
     *    'default_stream_class' => 'ClassName',
     * ]
     *
     * @param array $fileValues
     */
    function __construct(array $fileValues)
    {
        $this->from($fileValues);
    }


    /**
     * Set tmp_name of Uploaded File
     *
     * @param string $filepath
     *
     * @return $this
     */
    function setTmpName($filepath)
    {
        $this->tmpName = (string) $filepath;
        return $this;
    }

    /**
     * Get tmp_name Uploaded File
     *
     * @return null|string
     */
    function getTmpName()
    {
        return $this->tmpName;
    }

    /**
     * Set File Client Name
     *
     * @param string $name
     *
     * @return $this
     */
    function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Proxy to setName Method
     * @param $name
     * @return UploadedFile
     */
    function setClientFilename($name)
    {
        return $this->setName($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    function getClientFilename()
    {
        return $this->name;
    }

    /**
     * Set File Type
     *
     * @param string $type
     *
     * @return $this
     */
    function setType($type)
    {
        $this->type = (string) $type;
        return $this;
    }

    /**
     * Proxy To setType Method
     *
     * @param string $type
     * @return UploadedFile
     */
    function setClientMediaType($type)
    {
        return $this->setType($type);
    }

    /**
     * {@inheritdoc}
     */
    function getClientMediaType()
    {
        return $this->type;
    }


    /**
     * Set Stream
     *
     * @param iSResource|StreamInterface|iStreamable
     *        |resource $resource
     *
     * @return $this
     */
    function setStream($resource)
    {
        if (!$resource instanceof iSResource
            && !$resource instanceof StreamPsr
            && !$resource instanceof iStreamable
            && ! is_resource($resource)
        )
            throw new \InvalidArgumentException(
                'Stream must instance of iSResource, StreamInterface, iStreamable or php resource. '
                .' given: "%s"'
                , \Poirot\Core\flatten($resource)
            );


        $this->_t__stream = $resource; # stream will made of this when requested
        $this->stream     = null;      # reset stream fot getStream
        return $this;
    }

    /**
     * Get Streamed Object Of Uploaded File
     *
     * @return StreamInterface|iStreamable
     */
    function getStream()
    {
        if ($this->_hasMoved)
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');

        if ($this->stream)
            return $this->stream;

        $resource  = $this->_t__stream;
        $streamCls = $this->getDefaultStreamClass();

        if (!$resource) {
            ## stream not set, using tmp_name for stream
            if (!$tmpName = $this->getTmpName())
                throw new \InvalidArgumentException('Invalid stream or file provided for UploadedFile');

            $resource = (new WrapperClient($tmpName))->getConnect();
        }

        if (is_object($resource)) {
            if (is_a($resource, $streamCls))
                ## it's instance of default stream class
                return $this->stream = $resource;
            elseif (is_a($resource, 'Poirot\Stream\Psr\StreamPsr'))
                ## it's psr stream and default is another one
                return $this->stream = new $streamCls(
                    ($resource instanceof iSResource) ? $resource : new SResource($resource)
                );
            else {
                ## it's Streamable and must convert to Psr
                return $this->stream = new $streamCls($resource);
            }
        }

        return $this->stream = new $streamCls($resource);
    }

    /**
     * Set Size
     *
     * @param int $size
     *
     * @return $this
     */
    function setSize($size)
    {
        $this->size = (int) $size;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    function getSize()
    {
        return $this->size;
    }

    /**
     * Set Error Status Code
     *
     * @param int $errorStatus
     *
     * @return $this
     */
    function setError($errorStatus)
    {
        # error status
        if (! is_int($errorStatus)
            || 0 > $errorStatus
            || 8 < $errorStatus
        )
            throw new \InvalidArgumentException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant'
            );

        $this->error = $errorStatus;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    function getError()
    {
        return $this->error;
    }


    // ...

    /**
     * Set Default Stream Class
     *
     * @param object|string $class
     *
     * @return $this
     */
    function setDefaultStreamClass($class)
    {
        if (is_object($class))
            return $this->setDefaultStreamClass(get_class($class));

        $this->defaultStreamClass = (string) $class;
        return $this;
    }

    /**
     * Get Default Stream Class Name
     *
     * - iStreamable|StreamInterface
     *
     * @return string
     */
    function getDefaultStreamClass()
    {
        if (!$this->defaultStreamClass)
            $this->setDefaultStreamClass(self::DEFAULT_STREAM);

        return $this->defaultStreamClass;
    }

    /**
     * {@inheritdoc}
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    function moveTo($targetPath)
    {
        if ($this->_hasMoved)
            throw new \RuntimeException('Cannot move file; already moved!');


        $targetPath = (string) $targetPath;
        if (empty($targetPath))
            throw new \InvalidArgumentException(
                'Invalid path provided for move operation; must be a non-empty string'
            );

        if (strpos(PHP_SAPI, 'cli') === 0 || !$this->getTmpName())
            $this->__moveUploadedStreamFile($targetPath);
        elseif (move_uploaded_file($this->getTmpName(), $targetPath) === false)
            throw new \RuntimeException('Error occurred while moving uploaded file');

        $this->_hasMoved = true;
    }

    /**
     * Write internal stream to given path
     *
     * @param string $path
     */
    protected function __moveUploadedStreamFile($path)
    {
        $handle = fopen($path, 'wb+');
        if ($handle === false)
            throw new \RuntimeException('Unable to write to designated path');

        $stream = $this->getStream();
        if ($stream instanceof iStreamable) {
            $stream->rewind();
            while (! $stream->getResource()->isEOF())
                fwrite($handle, $this->getStream()->read(4096));
        } else {
            /** @var StreamInterface $stream */
            $stream->rewind();
            while (! $stream->eof())
                fwrite($handle, $this->getStream()->read(4096));
        }

        fclose($handle);
    }
}
