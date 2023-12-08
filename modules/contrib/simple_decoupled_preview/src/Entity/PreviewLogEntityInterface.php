<?php

namespace Drupal\simple_decoupled_preview\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining preview log entity entities.
 *
 * @ingroup simple_decoupled_preview
 */
interface PreviewLogEntityInterface extends ContentEntityInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the title of the logged entity.
   *
   * @return string
   *   The title of the logged entity.
   */
  public function getTitle();

  /**
   * Sets the title of the logged entity.
   *
   * @param string $title
   *   The title of the logged entity.
   *
   * @return \Drupal\simple_decoupled_preview\Entity\PreviewLogEntityInterface
   *   The called preview log entity.
   */
  public function setTitle($title);

  /**
   * Gets the preview log entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the preview log entity.
   */
  public function getCreatedTime();

  /**
   * Sets the preview log entity creation timestamp.
   *
   * @param int $timestamp
   *   The preview log entity creation timestamp.
   *
   * @return \Drupal\simple_decoupled_preview\Entity\PreviewLogEntityInterface
   *   The called preview log entity.
   */
  public function setCreatedTime($timestamp);

}
