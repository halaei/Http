<?php
namespace Poirot\Http\Message\Request;

use Poirot\Core\Interfaces\iDataSetConveyor;
use Poirot\Http\Header\HeaderFactory;
use Poirot\Http\Headers;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaderCollection;
use Poirot\Http\Psr\Interfaces\UploadedFileInterface;
use Poirot\Http\Psr\UploadedFile;
use Poirot\Http\Psr\Util;
use Poirot\Http\Util as UtilHttp;
use Poirot\Stream\Interfaces\iSResource;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamInterface;
use Poirot\Stream\Streamable\AggregateStream;
use Poirot\Stream\Streamable\TemporaryStream;

class BodyMultiPartStream implements iStreamable
{
    /** @var AggregateStream */
    protected $_t__wrapped_stream;
    /** @var string */
    protected $_boundary;
    /** @var false|TemporaryStream Last Trailing Boundary */
    protected $_trailingBoundary;

    /**
     * Construct
     *
     * @param array|string $multiPart _FILES, uploadedFiles, raw body string
     * @param null|string  $boundary
     */
    function __construct($multiPart = [], $boundary = null)
    {
        if ($multiPart instanceof iDataSetConveyor)
            $multiPart = $multiPart->toArray();

        if (!is_array($multiPart) && !is_string($multiPart))
            throw new \InvalidArgumentException(sprintf(
                'The Constructor give array of Files or Raw Body String, given: "%s".'
                , \Poirot\Core\flatten($multiPart)
            ));


        // ...

        $this->_t__wrapped_stream = new AggregateStream;
        if ($boundary === null)
            $this->_boundary = uniqid();


        if (is_array($multiPart) && !empty($multiPart))
            $this->_fromFilesArray($multiPart);
        else
            $this->_fromRawBodyString($multiPart);
    }

    /**
     * Build Stream By Parsing Raw Body
     *
     * ! parse string to array that can be used by class
     *
     * @param string $rawBody
     */
    protected function _fromRawBodyString($rawBody)
    {
        kd($rawBody);
    }

    /**
     * Build Stream From _FILES or uploadedFiles
     * @param $files
     */
    protected function _fromFilesArray($files)
    {
        if (current($files) instanceof UploadedFileInterface || isset($files['tmp_name']))
            $files = Util::normalizeFiles($files);

        foreach($files as $field => $file)
            $this->addElement($field, $file);
    }

    /**
     * Append Boundary Element
     *
     * @param string                      $fieldName Form Field Name
     * @param array|UploadedFileInterface $element
     * @param null|Headers|array          $headers   Extra Headers To Be Added
     * @return $this
     */
    function addElement($fieldName, $element, $headers = null)
    {
        $this->_trailingBoundary = false;

        if ($element instanceof UploadedFileInterface)
            $this->_addUploadedFileElement($fieldName, $element, $headers);
        elseif (is_array($element))
            $this->_addArrayElement($fieldName, $element);
        else
            throw new \InvalidArgumentException(sprintf(
                'Element must be defined array represent element or UploadedFileInterface. given: "%s".'
                , \Poirot\Core\flatten($element)
            ));

        return $this;
    }

    protected function _addUploadedFileElement($fieldName, UploadedFileInterface $element, $headers)
    {
        if (!$headers instanceof iHeaderCollection)
            $headers = ($headers) ? new Headers($headers) : new Headers;

        $headers->set(HeaderFactory::factory('Content-Type'
            , ($type = $element->getClientMediaType()) ? $type : 'application/octet-stream'
        ));

        if ($size = $element->getSize())
            $headers->set(HeaderFactory::factory('Content-Length', (string) $size));


        if ($element instanceof UploadedFile)
            ## using poirot stream
            $element->setDefaultStreamClass('\Poirot\Stream\Streamable');


        $this->__createElement($fieldName, $element->getStream(), $element->getClientFilename(), $headers);
    }

    protected function _addArrayElement($element)
    {

    }

    /**
     * @param string                      $name     Form Field Name
     * @param StreamInterface|iStreamable $stream
     * @param string                      $filename File name header
     * @param Headers|array               $headers  Boundary Headers
     *
     * @return array
     */
    protected function __createElement($name, $stream, $filename, $headers)
    {
        if (is_array($headers))
            $headers = new Headers($headers);
        elseif (!$headers instanceof Headers)
            throw new \InvalidArgumentException(sprintf(
                'Headers must be array or Header. given: "%s".'
                , \Poirot\Core\flatten($headers)
            ));

        // Set a default content-disposition header if one was no provided
        if (!$headers->has('content-disposition'))
            $headers->set(HeaderFactory::factory('Content-Disposition'
                , ($filename)
                    ? sprintf('form-data; name="%s"; filename="%s"'
                        , $name
                        , basename($filename)
                    )
                    : "form-data; name=\"{$name}\""
            ));

        // Set a default content-length header if one was no provided
        if (!$headers->has('content-length'))
            (!$length = $stream->getSize())
                ?: $headers->set(HeaderFactory::factory('Content-Length', (string) $length));


        // Set a default Content-Type if one was not supplied
        if (!$headers->has('content-type') && $filename)
            (!$type = UtilHttp::mimeTypeFromFilename($filename))
                ?: $headers->set(HeaderFactory::factory('Content-Type', $type));


        ## Add Created Element As Stream
        ## it included headers and body stream

        ### headers
        $renderHeaders = '';
        /** @var iHeader $h */
        $first = $headers->get('Content-Disposition');
        $renderHeaders .= $first->render()."\r\n";
        ## with new instance on delete
        $headers = $headers->del('Content-Disposition');
        foreach($headers as $h)
            $renderHeaders .= $h->render()."\r\n";
        $renderHeaders = "--{$this->_boundary}\r\n" . trim($renderHeaders) . "\r\n\r\n";

        $this->_t__wrapped_stream->addStream((new TemporaryStream($renderHeaders))->rewind());
        $this->_t__wrapped_stream->addStream($stream->rewind());
        $this->_t__wrapped_stream->addStream((new TemporaryStream("\r\n"))->rewind());
    }

    // ...

    /**
     * Set Stream Handler Resource
     *
     * @param iSResource $handle
     *
     * @return $this
     */
    function setResource(iSResource $handle)
    {
        $this->_t__wrapped_stream->setResource($handle);
        return $this;
    }

    /**
     * Get Stream Handler Resource
     *
     * @return iSResource
     */
    function getResource()
    {
        $this->_t__wrapped_stream->getResource();
        return $this;
    }

    /**
     * Set R/W Buffer Size
     *
     * @param int|null $buffer
     *
     * @return $this
     */
    function setBuffer($buffer)
    {
        $this->_t__wrapped_stream->setBuffer($buffer);
        return $this;
    }

    /**
     * Get Current R/W Buffer Size
     *
     * - usually null mean all stream content
     * - used as default $inByte argument value on
     *   read/write methods
     *
     * @return int|null
     */
    function getBuffer()
    {
        return $this->_t__wrapped_stream->getBuffer();
    }

    /**
     * Copies Data From One Stream To Another
     *
     * - If maxlength is not specified,
     *   all remaining content in source will be copied
     *
     * - reset and count into transCount
     *
     * @param iStreamable $destStream The destination stream
     * @param null $maxByte Maximum bytes to copy
     * @param int $offset The offset where to start to copy data
     *
     * @return $this
     */
    function pipeTo(iStreamable $destStream, $maxByte = null, $offset = 0)
    {
        $this->_t__wrapped_stream->pipeTo($destStream, $maxByte, $offset);
        return $this;
    }

    /**
     * Read Data From Stream
     *
     * - if $inByte argument not set, read entire stream
     *
     * @param int $inByte Read Data in byte
     *
     * @throws \Exception Error On Read Data
     * @return string
     */
    function read($inByte = null)
    {
        if (!$this->_trailingBoundary) {
            ## add trailing boundary as stream if not
            $this->_trailingBoundary = new TemporaryStream("--{$this->_boundary}--\r\n");
            $this->_t__wrapped_stream->addStream($this->_trailingBoundary->rewind());
        }

        return $this->_t__wrapped_stream->read($inByte);
    }

    /**
     * Gets line from stream resource up to a given delimiter
     *
     * Reading ends when length bytes have been read,
     * when the string specified by ending is found
     * (which is not included in the return value),
     * or on EOF (whichever comes first)
     *
     * ! does not return the ending delimiter itself
     *
     * @param string $ending
     * @param int $inByte
     *
     * @return string
     */
    function readLine($ending = "\n", $inByte = null)
    {
        if (!$this->_trailingBoundary) {
            ## add trailing boundary as stream if not
            $this->_trailingBoundary = new TemporaryStream("--{$this->_boundary}--\r\n");
            $this->_t__wrapped_stream->addStream($this->_trailingBoundary->rewind());
        }

        return $this->_t__wrapped_stream->readLine($ending, $inByte);
    }

    /**
     * Writes the contents of string to the file stream
     *
     * @param string $content The string that is to be written
     * @param int $inByte Writing will stop after length bytes
     *                          have been written or the end of string
     *                          is reached
     *
     * @return $this
     */
    function write($content, $inByte = null)
    {
        $this->_t__wrapped_stream->write($content, $inByte);
        return $this;
    }

    /**
     * Sends the specified data through the socket,
     * whether it is connected or not
     *
     * @param string $data The data to be sent
     * @param int|null $flags Provides a RDM (Reliably-delivered messages) socket
     *                        The value of flags can be any combination of the following:
     *                        - STREAM_SOCK_RDM
     *                        - STREAM_PEEK
     *                        - STREAM_OOB       process OOB (out-of-band) data
     *                        - null             auto choose the value
     *
     * @return $this
     */
    function sendData($data, $flags = null)
    {
        $this->_t__wrapped_stream->sendData($data, $flags);
        return $this;
    }

    /**
     * Receives data from a socket, connected or not
     *
     * @param int $maxByte
     * @param int $flags
     *
     * @return string
     */
    function receiveFrom($maxByte, $flags = STREAM_OOB)
    {
        return $this->_t__wrapped_stream->receiveFrom($maxByte, $flags);
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    function getSize()
    {
        return $this->_t__wrapped_stream->getSize();
    }

    /**
     * Get Total Count Of Bytes After Each Read/Write
     *
     * @return int
     */
    function getTransCount()
    {
        return $this->_t__wrapped_stream->getTransCount();
    }

    /**
     * @link http://php.net/manual/en/function.fseek.php
     *
     * Move the file pointer to a new position
     *
     * - The new position, measured in bytes from the beginning of the file,
     *   is obtained by adding $offset to the position specified by $whence.
     *
     * ! php doesn't support seek/rewind on non-local streams
     *   we can using temp/cache piped stream.
     *
     * ! If you have opened the file in append ("a" or "a+") mode,
     *   any data you write to the file will always be appended,
     *   regardless of the file position.
     *
     * @param int $offset
     * @param int $whence Accepted values are:
     *              - SEEK_SET - Set position equal to $offset bytes.
     *              - SEEK_CUR - Set position to current location plus $offset.
     *              - SEEK_END - Set position to end-of-file plus $offset.
     *
     * @return $this
     */
    function seek($offset, $whence = SEEK_SET)
    {
        $this->_t__wrapped_stream->seek($offset, $whence);
        return $this;
    }

    /**
     * Move the file pointer to the beginning of the stream
     *
     * ! php doesn't support seek/rewind on non-local streams
     *   we can using temp/cache piped stream.
     *
     * ! If you have opened the file in append ("a" or "a+") mode,
     *   any data you write to the file will always be appended,
     *   regardless of the file position.
     *
     * @return $this
     */
    function rewind()
    {
        $this->_t__wrapped_stream->rewind();
        return $this;
    }
}
