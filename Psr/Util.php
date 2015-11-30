<?php
namespace Poirot\Http\Psr;

use Poirot\Http\Psr\Interfaces\MessageInterface;
use Poirot\Http\Psr\Interfaces\RequestInterface;
use Poirot\Http\Psr\Interfaces\ResponseInterface;
use Poirot\Http\Psr\Interfaces\UploadedFileInterface;

class Util
{
    /**
     * String representation of an HTTP message.
     *
     * @param MessageInterface $httpMessage
     *
     * @return string
     */
    static function messageToString(MessageInterface $httpMessage)
    {
        if ($httpMessage instanceof RequestInterface) {
            $msg = trim($httpMessage->getMethod() . ' '
                    . $httpMessage->getRequestTarget())
                . ' HTTP/' . $httpMessage->getProtocolVersion();
            if (!$httpMessage->hasHeader('host'))
                $msg .= "\r\nHost: " . $httpMessage->getUri()->getHost();
        } elseif ($httpMessage instanceof ResponseInterface) {
            $msg = 'HTTP/' . $httpMessage->getProtocolVersion() . ' '
                . $httpMessage->getStatusCode() . ' '
                . $httpMessage->getReasonPhrase();
        } else
            throw new \InvalidArgumentException('Unknown message type');

        foreach ($httpMessage->getHeaders() as $name => $values)
            $msg .= "\r\n{$name}: " . implode(', ', $values);

        return "{$msg}\r\n\r\n" . $httpMessage->getBody();
    }

    /**
     * Normalize uploaded files
     *
     * Transforms each value into an UploadedFileInterface instance, and ensures
     * that nested arrays are normalized.
     *
     * @param array $files
     * @return array
     *
     * @throws \InvalidArgumentException for unrecognized values
     */
    static function normalizeFiles(array $files, $stream = null)
    {
        $normalized = [];
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::__createUploadedFileFromSpec($value, $stream);
                continue;
            }

            if (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value, $stream);
                continue;
            }

            throw new \InvalidArgumentException('Invalid value in files specification');
        }

        return $normalized;
    }


    // ...

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    protected static function __createUploadedFileFromSpec(array $value, $stream)
    {
        if (is_array($value['tmp_name']))
            return self::__normalizeNestedFileSpec($value, $stream);

        ($value === null) ?: $value['default_stream_class'] = $stream;
        return new UploadedFile($value);
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files
     * @return UploadedFileInterface[]
     */
    protected static function __normalizeNestedFileSpec(array $files, $stream)
    {
        $files = [];
        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $files[$key] = self::__createUploadedFileFromSpec($spec, $stream);
        }

        return $files;
    }
}
