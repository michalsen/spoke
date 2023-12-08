<?php

namespace Drupal\simple_decoupled_preview_jsonapi;

use Drupal\jsonapi\IncludeResolver as BaseIncludeResolver;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\Data;
use Drupal\jsonapi\JsonApiResource\IncludedData;
use Drupal\jsonapi\JsonApiResource\LabelOnlyResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;

/**
 * Resolves included resources for an entity or collection of entities.
 *
 * Overwrites several methods from Drupal\jsonapi\IncludeResolver, adding
 * support for $in_preview parameter.
 *
 * @see \Drupal\jsonapi\IncludeResolver
 */
class IncludeResolver extends BaseIncludeResolver {

  /**
   * {@inheritdoc}
   *
   * Overwrites Drupal\jsonapi\IncludeResolver::resolve() with support for
   * $in_preview parameter. Due to IncludeResolver::resolve() being marked
   * internal, keep in sync with that method whenever it changes.
   *
   * @param \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface|\Drupal\jsonapi\JsonApiResource\ResourceObjectData $data
   *   The resource(s) for which to resolve includes.
   * @param string $include_parameter
   *   The include query parameter to resolve.
   * @param bool $in_preview
   *   A flag to tell whether data is in preview mode.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function resolve($data, $include_parameter, bool $in_preview = FALSE) {
    assert($data instanceof ResourceObject || $data instanceof ResourceObjectData);
    $data = $data instanceof ResourceObjectData ? $data : new ResourceObjectData([$data], 1);
    $include_tree = static::toIncludeTree($data, $include_parameter);
    return IncludedData::deduplicate($this->resolveIncludeTree($include_tree, $data, NULL, $in_preview));
  }

  /**
   * {@inheritdoc}
   *
   * Overwrites Drupal\jsonapi\IncludeResolver::resolveIncludeTree() with
   * support for $in_preview parameter. Due to
   * IncludeResolver::resolveIncludeTree() being marked internal, keep in sync
   * with that method whenever it changes.
   *
   * Search for "// CUSTOM MODIFICATION" to easily keep track of changed lines
   * in this method.
   *
   * @param array $include_tree
   *   The include paths, represented as a tree.
   * @param \Drupal\jsonapi\JsonApiResource\Data $data
   *   The entity collection from which includes should be resolved.
   * @param \Drupal\jsonapi\JsonApiResource\Data|null $includes
   *   (Internal use only) Any prior resolved includes.
   * @param bool $in_preview
   *   A flag to tell whether data is in preview mode.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function resolveIncludeTree(array $include_tree, Data $data, Data $includes = NULL, bool $in_preview = FALSE) {
    $includes = is_null($includes) ? new IncludedData([]) : $includes;
    foreach ($include_tree as $field_name => $children) {
      $references = [];
      foreach ($data as $resource_object) {
        // Some objects in the collection may be LabelOnlyResourceObjects or
        // EntityAccessDeniedHttpException objects.
        assert($resource_object instanceof ResourceIdentifierInterface);
        $public_field_name = $resource_object->getResourceType()->getPublicName($field_name);

        if ($resource_object instanceof LabelOnlyResourceObject) {
          $message = "The current user is not allowed to view this relationship.";
          $exception = new EntityAccessDeniedHttpException($resource_object->getEntity(), AccessResult::forbidden("The user only has authorization for the 'view label' operation."), '', $message, $public_field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        elseif (!$resource_object instanceof ResourceObject) {
          continue;
        }

        // Not all entities in $entity_collection will be of the same bundle and
        // may not have all of the same fields. Therefore, calling
        // $resource_object->get($a_missing_field_name) will result in an
        // exception.
        if (!$resource_object->hasField($public_field_name)) {
          continue;
        }
        $field_list = $resource_object->getField($public_field_name);
        // Config entities don't have real fields and can't have relationships.
        if (!$field_list instanceof FieldItemListInterface) {
          continue;
        }
        $field_access = $field_list->access('view', NULL, TRUE);
        if (!$field_access->isAllowed()) {
          $message = 'The current user is not allowed to view this relationship.';
          $exception = new EntityAccessDeniedHttpException($field_list->getEntity(), $field_access, '', $message, $public_field_name);
          $includes = IncludedData::merge($includes, new IncludedData([$exception]));
          continue;
        }
        $target_type = $field_list->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
        assert(!empty($target_type));
        foreach ($field_list as $field_item) {
          assert($field_item instanceof EntityReferenceItem);
          // CUSTOM MODIFICATION STARTS.
          // When in preview, use the already loaded entity from fields,
          // instead of loading them from database.
          $references[$target_type][] = $in_preview ? $field_item->entity : NULL;
          // CUSTOM MODIFICATION ENDS.
        }
      }
      // CUSTOM MODIFICATION STARTS.
      foreach ($references as $id_group) {
        // Create a sorted array of targeted entities.
        $targeted_entities = [];
        foreach ($id_group as $id => $entity) {
          if ($entity instanceof EntityInterface) {
            $targeted_entities[$id] = $entity;
          }
        }

        foreach ($targeted_entities as $entity) {
          if ($entity instanceof EntityInterface) {
            $entity->mergeCacheMaxAge(0);
          }
        }
        // CUSTOM MODIFICATION ENDS.
        $access_checked_entities = array_map(function (EntityInterface $entity) {
          return $this->entityAccessChecker->getAccessCheckedResourceObject($entity);
        }, $targeted_entities);
        $targeted_collection = new IncludedData(array_filter($access_checked_entities, function (ResourceIdentifierInterface $resource_object) {
          return !$resource_object->getResourceType()->isInternal();
        }));
        $includes = static::resolveIncludeTree($children, $targeted_collection, IncludedData::merge($includes, $targeted_collection), $in_preview);
      }
    }
    return $includes;
  }

}
