<?php

namespace Drupal\build_scripts;

/**
 * Build manager contains functions to communicate with build programs.
 */
interface BuildManagerInterface {

  /**
   * Returns the build job logs.
   *
   * @param string $build_id
   *   Id of the build job to poll.
   *
   * @return \Drupal\build_scripts\BuildLogStreamInterface
   *   Build log stream.
   */
  public function streamLogs($build_id);

  /**
   * Start new build job.
   *
   * @return string
   *   Build job id.
   */
  public function start($stage, $language);

}
