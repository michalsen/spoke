<?php

namespace Drupal\build_scripts;

use Psr\Http\Message\SEEK_SET;

/**
 * Wrapper around Guzzle response streaming.
 */
class BuildLogStream implements BuildLogStreamInterface {

  /**
   * Build daemon response.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  protected $response;

  /**
   * Construct the BulidLogStream object.
   *
   * @param \Psr\Http\Message\ResponseInterface|null $response
   *   Response from build daemon log request.
   */
  public function __construct($response) {
    $this->response = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode() {
    if (is_null($this->response)) {
      return self::STATUS_UNAVAILABLE;
    }

    return $this->response->getStatusCode();
  }

  /**
   * Get \Psr\Http\Message\ResponseInterface.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   Stream that is wrapped by this class.
   */
  private function getStream() {
    if (is_null($this->response)) {
      throw new \Exception("Log stream is unavailable.");
    }

    return $this->response->getBody();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->getStream()->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    return $this->close();
  }

  /**
   * {@inheritdoc}
   */
  public function detach() {
    return $this->getStream()->detach();
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->getStream()->getSize();
  }

  /**
   * {@inheritdoc}
   */
  public function tell() {
    return $this->getStream()->tell();
  }

  /**
   * {@inheritdoc}
   */
  public function eof() {
    return $this->getStream()->eof();
  }

  /**
   * {@inheritdoc}
   */
  public function isSeekable() {
    return $this->getStream()->isSeekable();
  }

  /**
   * {@inheritdoc}
   */
  public function seek($offset, $whence = SEEK_SET) {
    return $this->getStream()->seek($offset, $whence);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    return $this->getStream()->rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function isWritable() {
    return $this->getStream()->isWritable();
  }

  /**
   * {@inheritdoc}
   */
  public function write($string) {
    return $this->getStream()->write($string);
  }

  /**
   * {@inheritdoc}
   */
  public function isReadable() {
    return $this->getStream()->isReadable();
  }

  /**
   * {@inheritdoc}
   */
  public function read($length) {
    return $this->getStream()->read($length);
  }

  /**
   * {@inheritdoc}
   */
  public function getContents() {
    return $this->getStream()->getContents();
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key = NULL) {
    return $this->getStream()->getMetadata($key);
  }

}
