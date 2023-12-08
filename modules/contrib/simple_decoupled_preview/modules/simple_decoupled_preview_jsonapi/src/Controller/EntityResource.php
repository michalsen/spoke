<?php

namespace Drupal\simple_decoupled_preview_jsonapi\Controller;

use Drupal\jsonapi\Controller\EntityResource as BaseEntityResource;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\NullIncludedData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\ResourceResponse;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Process node preview requests.
 *
 * @see \Drupal\jsonapi\Controller\EntityResource
 */
class EntityResource extends BaseEntityResource {

  /**
   * The include resolver.
   *
   * @var \Drupal\simple_decoupled_preview_jsonapi\IncludeResolver
   */
  protected $includeResolver;

  /**
   * Gets the preview of a node.
   *
   * @param \Drupal\node\NodeInterface $node_preview
   *   The node being previewed.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The response.
   *
   * @throws \Drupal\jsonapi\Exception\EntityAccessDeniedHttpException
   *   Thrown when access to the entity is not allowed.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getIndividualNodePreview(NodeInterface $node_preview, Request $request): ResourceResponse {
    // Avoid caching previews at all.
    $node_preview->mergeCacheMaxAge(0);

    $resource_object = $this->entityAccessChecker->getAccessCheckedResourceObject($node_preview);
    if ($resource_object instanceof EntityAccessDeniedHttpException) {
      throw $resource_object;
    }
    $primary_data = new ResourceObjectData([$resource_object], 1);
    // Get includes in preview mode.
    return $this->buildWrappedResponse($primary_data, $request, $this->getIncludes($request, $primary_data, TRUE));
  }

  /**
   * {@inheritdoc}
   *
   * Overwrites Drupal\jsonapi\Controller\EntityResource::getIncludes() with
   * support for $in_preview parameter. Due to EntityResource::getIncludes()
   * being marked as internal, keep in sync with that method whenever it
   * changes.
   *
   * @param bool $in_preview
   *   A flag to tell whether data is in preview mode.
   */
  public function getIncludes(Request $request, $data, bool $in_preview = FALSE) {
    assert($data instanceof ResourceObject || $data instanceof ResourceObjectData);
    $include_resolver = \Drupal::service('simple_decoupled_preview_jsonapi.include_resolver');
    return $request->query->has('include') && ($include_parameter = $request->query->get('include')) && !empty($include_parameter)
      ? $include_resolver->resolve($data, $include_parameter, $in_preview)
      : new NullIncludedData();
  }

}
