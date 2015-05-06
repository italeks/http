<?php
namespace Icicle\Http\Message;

use Icicle\Stream\ReadableStreamInterface;

/**
 * HTTP message interface based on PSR-7, modified to use promise-based streams.
 */
interface MessageInterface
{
    /**
     * @return  string
     */
    public function getProtocolVersion();

    /**
     * Returns the message headers as a string-indexed array of arrays of strings or an empty array if no headers
     * have been set.
     *
     * @return  string[][]
     */
    public function getHeaders();

    /**
     * Determines if the message has the given header.
     *
     * @param   string $name
     *
     * @return  bool
     */
    public function hasHeader($name);

    /**
     * Returns the array of values for the given header or an empty array if the header does not exist.
     *
     * @param   string $name
     *
     * @return  string[]
     */
    public function getHeader($name);

    /**
     * Returns the values for the given header as a comma separated list or null if the header does not exist.
     *
     * @param   string $name
     *
     * @return  string|null
     */
    public function getHeaderLine($name);

    /**
     * Returns all the set headers as an array of comma separated lists of values. Returns an empty array if no
     * headers have been set.
     *
     * @return  string[]
     */
    public function getHeaderLines();

    /**
     * Returns the stream for the message body.
     *
     * @return  \Icicle\Stream\ReadableStreamInterface
     */
    public function getBody();

    /**
     * Returns a new instance with the given protocol version.
     *
     * @param   string $version
     *
     * @return  static
     */
    public function withProtocolVersion($version);

    /**
     * Returns a new instance with the given header. $value may be a string or an array of strings.
     *
     * @param   string $name
     * @param   string|string[] $value
     *
     * @return  static
     */
    public function withHeader($name, $value);

    /**
     * Returns a new instance with the given value added to the named header. If the header did not exist, the header
     * is created with the given value.
     *
     * @param   string $name
     * @param   string|string[] $value
     *
     * @return  static
     */
    public function withAddedHeader($name, $value);

    /**
     * Returns a new instance without the given header.
     *
     * @param   string $name
     *
     * @return  static
     */
    public function withoutHeader($name);

    /**
     * Returns a new instance with the given stream for the message body.
     *
     * @param   \Icicle\Stream\ReadableStreamInterface $stream
     *
     * @return  static
     */
    public function withBody(ReadableStreamInterface $stream);
}