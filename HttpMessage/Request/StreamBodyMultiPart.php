<?php
namespace Poirot\Http\HttpMessage\Request;

use Poirot\Http\Header\CollectionHeader;
use Poirot\Http\Header\FactoryHttpHeader;
use Poirot\Http\Interfaces\iHeader;
use Poirot\Http\Interfaces\iHeaders;
use Poirot\Http\Header as UtilHttp;
use Poirot\Stream\Interfaces\iStreamable;
use Poirot\Stream\Psr\StreamBridgeFromPsr;
use Poirot\Stream\Streamable\SAggregateStreams;
use Poirot\Stream\Streamable\SDecorateStreamable;
use Poirot\Stream\Streamable\STemporary;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;


/**
 * RFC 1867 - Form-based File Upload in HTML
 * @link http://www.faqs.org/rfcs/rfc1867.html
 * TODO multipart/mixed
 */
class StreamBodyMultiPart 
    extends SDecorateStreamable
    implements iStreamable
{
    /** @var SAggregateStreams */
    protected $_t__wrap_stream;

    /** @var string */
    protected $_boundary;
    /** @var false|STemporary Last Trailing Boundary */
    protected $_trailingBoundary;
    
    /** @var array Elements Added To MultiPart Body*/
    protected $elementsAdded = array(
        # 'field_name' => (iStreamable) | (string) | (UploadedFileInterface)
    );


    /**
     * Construct
     *
     * @param array|string $multiPart _FILES, uploadedFiles, raw body string
     * @param null|string  $boundary
     */
    function __construct($multiPart = null, $boundary = null)
    {
        parent::__construct(new SAggregateStreams);
        
        
        if ($boundary === null)
            $boundary = '----WebKitFormBoundary'.uniqid();
        
        $this->_boundary = (string) $boundary;

        if ($multiPart !== null)
            $this->addElements($multiPart);
    }

    /**
     * Append Boundary Elements
     * 
     * @param array|string $multiPart
     * 
     * @return $this
     */
    function addElements($multiPart)
    {
        if (! is_array($multiPart) )
            throw new \InvalidArgumentException(sprintf(
                'Accept array of Files; given: "%s".'
                , \Poirot\Std\flatten($multiPart)
            ));


        foreach($multiPart as $name => $element)
            $this->addElement($name, $element);
        
        $this->addElementDone();
        
        return $this;
    }
    
    /**
     * Append Boundary Element
     *
     * @param string                                       $fieldName Form Field Name
     * @param UploadedFileInterface|string|StreamInterface $element
     * @param null|array                                   $headers   Extra Headers To Be Added
     * 
     * @return $this
     * @throws \Exception
     */
    function addElement($fieldName, $element, $headers = null)
    {
        if ($this->_trailingBoundary)
            throw new \Exception('Trailing Boundary Is Added.');
        
        if (!$headers instanceof iHeaders)
            $headers = ($headers) ? new CollectionHeader($headers) : new CollectionHeader;
        
        
        $headers = clone $headers;

        if ($element instanceof UploadedFileInterface)
            $this->_addUploadedFileElement($fieldName, $element, $headers);
        elseif (\Poirot\Std\isStringify($element) || $element instanceof iStreamable)
            $this->_addTextElement($fieldName, $element, $headers);
        elseif (is_array($element)) {
            // use the infamous input name="array[]"
            foreach ($element as $k => $v) 
                $this->_addTextElement($fieldName."[$k]", $v, $headers);
        } else
            throw new \InvalidArgumentException(sprintf(
                'Element must be defined array represent element or UploadedFileInterface. given: "%s".'
                , \Poirot\Std\flatten($element)
            ));

        $this->elementsAdded[$fieldName] = $element; 
        return $this;
    }

    /**
     * Get Elements Lists Added To StreamBody
     * 
     * @return array
     */
    function listElements()
    {
        return $this->elementsAdded;
    }
    
    /**
     * Add Trailing Boundary And Finish Data
     * 
     * @return void
     */
    function addElementDone()
    {
        ## add trailing boundary as stream if not
        $this->_trailingBoundary = new STemporary("--{$this->_boundary}--");
        $this->_t__wrap_stream->addStream($this->_trailingBoundary->rewind());
    }
    
    
    // ...

    /**
     * @param string                $fieldName
     * @param UploadedFileInterface $element
     * @param CollectionHeader      $headers
     */
    protected function _addUploadedFileElement($fieldName, UploadedFileInterface $element, $headers)
    {
        $headers->insert(FactoryHttpHeader::of(
            array('Content-Type' => ($type = $element->getClientMediaType()) ? $type : 'application/octet-stream')
        ));

        if ($size = $element->getSize())
            $headers->insert(FactoryHttpHeader::of( array('Content-Length' => (string) $size)) );
        else
            // Set a default content-length header if it was no provided
            $headers->insert(FactoryHttpHeader::of(
                array( 'Content-Length' => (string) $element->getStream()->getSize() )
            ));

        
        // Set a default content-disposition header if one was no provided
        $headers->insert(FactoryHttpHeader::of(
            array( 'Content-Disposition' => sprintf(
                    'form-data; name="%s"; filename="%s"'
                    , $fieldName
                    , basename($element->getClientFilename())
                )
            )
        ));
        
        
        $this->_createElement($fieldName, $element->getStream(), $headers);
    }

    protected function _addTextElement($fieldName, $element, $headers)
    {
        if (!$element instanceof StreamInterface && !$element instanceof iStreamable)
            $element = new STemporary( trim( (string) $element) );

        $this->_createElement($fieldName, $element, $headers);
    }

    /**
     * @param string                      $name     Form Field Name
     * @param StreamInterface|iStreamable $stream
     * @param CollectionHeader            $headers  Boundary Headers
     *
     * @return array
     */
    protected function _createElement($name, $stream, CollectionHeader $headers)
    {
        if (!$headers->has('Content-Disposition'))
            $headers->insert(FactoryHttpHeader::of(
                array( 'Content-Disposition' => "form-data; name=\"{$name}\"" )
            ));


        ## Add Created Element As Stream
        ## it included headers and body stream

        ### headers
        $renderHeaders = '';
        /** @var iHeader $first */
        foreach ($headers->get('Content-Disposition') as $first)
            $renderHeaders .= $first->render()."\r\n";

        
        ## with new instance on delete
        $headers = $headers->del('Content-Disposition');
        /** @var iHeader $h */
        foreach($headers as $h)
            $renderHeaders .= $h->render()."\r\n";

        $renderHeaders = "--{$this->_boundary}\r\n" .$renderHeaders. "\r\n";


        $tStream = new STemporary($renderHeaders);
        $this->_t__wrap_stream->addStream($tStream->rewind());

        ($stream instanceof iStreamable) ?: $stream = new StreamBridgeFromPsr($stream);
        $this->_t__wrap_stream->addStream($stream);
        $this->_t__wrap_stream->addStream(new STemporary("\r\n"));
    }
}
