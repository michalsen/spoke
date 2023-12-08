<?php

namespace Drupal\build_scripts;

use Psr\Http\Message\StreamInterface;

/**
 * Build log stream represents build process output.
 */
interface BuildLogStreamInterface extends StreamInterface {

  /**
   * Value indicating that log stream is ok.
   */
  const STATUS_OK = 200;

  /**
   * Value indicating that log stream has no content (i.e. logs are truncated).
   */
  const STATUS_NO_CONTENT = 204;

  /**
   * Value indicating that requesting log stream failed.
   */
  const STATUS_NOT_FOUND = 404;

  /**
   * Value indicating that requesting log stream failed.
   */
  const STATUS_UNAVAILABLE = 503;

  /**
   * Returns the log stream status.
   *
   * @return int
   *   Build job status that is HTTP status code compliant.
   */
  public function getStatusCode();

}
