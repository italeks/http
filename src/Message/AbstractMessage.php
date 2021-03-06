<?php
namespace Icicle\Http\Message;

use Icicle\Http\Exception\{InvalidHeaderException, UnsupportedVersionException};
use Icicle\Stream\{ReadableStream, MemorySink};

abstract class AbstractMessage implements Message
{
    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var string[]
     */
    private $headerNameMap = [];

    /**
     * @var string[][]
     */
    private $headers = [];

    /**
     * @var \Icicle\Stream\ReadableStream
     */
    private $stream;

    /**
     * @param string[][] $headers
     * @param \Icicle\Stream\ReadableStream|null $stream
     * @param string $protocol
     *
     * @throws \Icicle\Http\Exception\MessageException
     */
    public function __construct(array $headers = [], ReadableStream $stream = null, string $protocol = '1.1')
    {
        if (!empty($headers)) {
            $this->addHeaders($headers);
        }

        $this->stream = $stream ?: new MemorySink();
        $this->protocol = $this->filterProtocolVersion($protocol);
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNameMap[strtolower($name)]);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderAsArray(string $name): array
    {
        $name = strtolower($name);

        if (!isset($this->headerNameMap[$name])) {
            return [];
        }

        $name = $this->headerNameMap[$name];

        return $this->headers[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name): string
    {
        $name = strtolower($name);

        if (!isset($this->headerNameMap[$name])) {
            return '';
        }

        $name = $this->headerNameMap[$name];

        return isset($this->headers[$name][0]) ? $this->headers[$name][0] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): ReadableStream
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion(string $version): Message
    {
        $new = clone $this;
        $new->protocol = $new->filterProtocolVersion($version);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader(string $name, $value): Message
    {
        $new = clone $this;
        $new->setHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader(string $name, $value): Message
    {
        $new = clone $this;
        $new->addHeader($name, $value);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader(string $name): Message
    {
        $new = clone $this;
        $new->removeHeader($name);
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(ReadableStream $stream): Message
    {
        $new = clone $this;
        $new->stream = $stream;
        return $new;
    }

    /**
     * Sets the headers from the given array.
     *
     * @param string[] $headers
     */
    protected function setHeaders(array $headers)
    {
        $this->headerNameMap = [];
        $this->headers = [];

        $this->addHeaders($headers);
    }

    /**
     * Adds headers from the given array.
     *
     * @param string[] $headers
     */
    protected function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    /**
     * Sets the named header to the given value.
     *
     * @param string $name
     * @param string|string[] $value
     *
     * @throws \Icicle\Http\Exception\InvalidHeaderException If the header name or value is invalid.
     */
    protected function setHeader(string $name, $value)
    {
        if (!$this->isHeaderNameValid($name)) {
            throw new InvalidHeaderException('Header name is invalid.');
        }

        $normalized = strtolower($name);
        $value = $this->filterHeader($value);

        // Header may have been previously set with a different case. If so, remove that header.
        if (isset($this->headerNameMap[$normalized]) && $this->headerNameMap[$normalized] !== $name) {
            unset($this->headers[$this->headerNameMap[$normalized]]);
        }

        $this->headerNameMap[$normalized] = $name;
        $this->headers[$name] = $value;
    }

    /**
     * Adds the value to the named header, or creates the header with the given value if it did not exist.
     *
     * @param string $name
     * @param string|string[] $value
     *
     * @throws \Icicle\Http\Exception\InvalidHeaderException If the header name or value is invalid.
     */
    protected function addHeader(string $name, $value)
    {
        if (!$this->isHeaderNameValid($name)) {
            throw new InvalidHeaderException('Header name is invalid.');
        }

        $normalized = strtolower($name);
        $value = $this->filterHeader($value);

        if (isset($this->headerNameMap[$normalized])) {
            $name = $this->headerNameMap[$normalized]; // Use original case to add header value.
            $this->headers[$name] = array_merge($this->headers[$name], $value);
        } else {
            $this->headerNameMap[$normalized] = $name;
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes the given header if it exists.
     *
     * @param string $name
     */
    protected function removeHeader(string $name)
    {
        $normalized = strtolower($name);

        if (isset($this->headerNameMap[$normalized])) {
            $name = $this->headerNameMap[$normalized];
            unset($this->headers[$name], $this->headerNameMap[$normalized]);
        }
    }

    /**
     * @param string $protocol
     *
     * @return string
     *
     * @throws \Icicle\Http\Exception\UnsupportedVersionException If the protocol is not valid.
     */
    private function filterProtocolVersion(string $protocol)
    {
        switch ($protocol) {
            case '1.1':
            case '1.0':
                return $protocol;

            default:
                throw new UnsupportedVersionException('Invalid protocol version.');
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function isHeaderNameValid(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9`~!#$%^&_|\'\-]+$/', $name);
    }

    /**
     * Converts a given header value to an integer-indexed array of strings.
     *
     * @param mixed|mixed[] $values
     *
     * @return string[]
     *
     * @throws \Icicle\Http\Exception\InvalidHeaderException If the given value cannot be converted to a string and
     *     is not an array of values that can be converted to strings.
     */
    private function filterHeader($values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $lines = [];

        foreach ($values as $value) {
            if (is_numeric($value) || is_null($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $value = (string) $value;
            } elseif (!is_string($value)) {
                throw new InvalidHeaderException('Header values must be strings or an array of strings.');
            }

            if (preg_match("/[^\t\r\n\x20-\x7e\x80-\xfe]|\r\n/", $value)) {
                throw new InvalidHeaderException('Invalid character(s) in header value.');
            }

            $lines[] = $value;
        }

        return $lines;
    }
}