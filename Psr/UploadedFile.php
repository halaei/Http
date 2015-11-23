<?php
namespace Poirot\Http\Psr;

use Poirot\Http\Psr\Interfaces\UploadedFileInterface;
use Poirot\Stream\Interfaces\iSResource;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\Psr\StreamPsr;

class UploadedFile implements UploadedFileInterface
{
    /** @var null|string */
    protected $file;
    /** @var null|StreamInterface */
    protected $stream;

    /** @var int */
    protected $size;
    /** @var int */
    protected $error;

    /** @var string */
    protected $clientFilename;
    /** @var string */
    protected $clientMediaType;

    /** @var bool */
    protected $_hasMoved = false;


    /**
     * Construct
     *
     * @param string|resource|StreamInterface|iSResource $streamOrFile
     * @param $size
     * @param $errorStatus
     * @param null $clientFilename
     * @param null $clientMediaType
     *
     * @throws \InvalidArgumentException
     */
    function __construct($streamOrFile, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
    {
        # file stream
        if (is_string($streamOrFile))
            $this->file = $streamOrFile;

        if (is_string($streamOrFile) || is_resource($streamOrFile) || $streamOrFile instanceof iSResource)
            $this->stream = new StreamPsr($streamOrFile);

        if ($streamOrFile instanceof StreamInterface)
            $this->stream = $streamOrFile;

        if (! $this->file && ! $this->stream)
            throw new \InvalidArgumentException('Invalid stream or file provided for UploadedFile');

        # size
        $this->size = (int) $size;

        # error status
        if (! is_int($errorStatus)
            || 0 > $errorStatus
            || 8 < $errorStatus
        )
            throw new \InvalidArgumentException(
                'Invalid error status for UploadedFile; must be an UPLOAD_ERR_* constant'
            );

        $this->error = $errorStatus;

        # file['name']
        $this->clientFilename = (string) $clientFilename;

        # file['type']
        $this->clientMediaType = (string) $clientMediaType;
    }

    /**
     * {@inheritdoc}
     */
    function getStream()
    {
        if ($this->_hasMoved)
            throw new \RuntimeException('Cannot retrieve stream after it has already been moved');

        return $this->stream;
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

        if (strpos(PHP_SAPI, 'cli') === 0 || !$this->file)
            $this->__moveUploadedStreamFile($targetPath);
        elseif (move_uploaded_file($this->file, $targetPath) === false)
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

            $this->getStream()->rewind();
            while (! $this->stream->eof())
                fwrite($handle, $this->getStream()->read(4096));

            fclose($handle);
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
     * {@inheritdoc}
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    function getError()
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    function getClientFilename()
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}
