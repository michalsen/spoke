<?php

namespace Drupal\simple_decoupled_preview\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Preview log entity entities.
 */
class PreviewLogEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
