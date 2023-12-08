<?php

namespace Drupal\simple_decoupled_preview_jsonapi\EventSubscriber;

use Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher as BaseResourceObjectNormalizationCacher;
use Drupal\jsonapi\JsonApiResource\ResourceObject;

/**
 * Caches entity normalizations after the response has been sent.
 *
 * Extends the base service from JSON:API to be able to generate a lookup render
 * array that supports max-age.
 *
 * @see \Drupal\jsonapi\EventSubscriber\ResourceObjectNormalizationCacher
 */
class ResourceObjectNormalizationCacher extends BaseResourceObjectNormalizationCacher {

  /**
   * {@inheritdoc}
   *
   * Adds max-age to the render array so normalizations that should not be
   * cached, like node previews, are not stored into cache.
   */
  protected static function generateLookupRenderArray(ResourceObject $object) {
    $lookupRenderArray = parent::generateLookupRenderArray($object);
    if (isset($lookupRenderArray['#cache']) && is_array($lookupRenderArray['#cache'])) {
      $lookupRenderArray['#cache']['max-age'] = $object->getCacheMaxAge();
    }
    return $lookupRenderArray;
  }

}
