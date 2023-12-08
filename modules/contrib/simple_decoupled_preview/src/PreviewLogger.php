<?php

namespace Drupal\simple_decoupled_preview;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\simple_decoupled_preview_jsonapi\EntityToJsonApiPreview;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Defines a service for logging content entity changes using log entities.
 */
class PreviewLogger {

  /**
   * Config Interface for accessing site configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\simple_decoupled_preview_jsonapi\EntityToJsonApiPreview definition.
   *
   * @var \Drupal\simple_decoupled_preview_jsonapi\EntityToJsonApiPreview
   */
  private $entityToJsonApiPreview;

  /**
   * Drupal\Core\Entity\EntityRepository definition.
   *
   * @var \Drupal\Core\Entity\EntityRepository
   */
  private $entityRepository;

  /**
   * Constructs a new PreviewEntityLogger object.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    EntityTypeManagerInterface $entity_type_manager,
    EntityRepository $entity_repository,
    EntityToJsonApiPreview $entity_to_json_api_preview
  ) {
    $this->config = $config->get('simple_decoupled_preview.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityToJsonApiPreview = $entity_to_json_api_preview;
  }

  /**
   * Logs an entity create, update, or delete.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity to log the details for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function logEntity(NodeInterface $entity): void {
    $user_id = \Drupal::currentUser()->id();
    $this->deleteLoggedEntity($entity->uuid(), $user_id, $entity->language()->getId());

    // Generate the full JSON representation of this entity so it can be
    // transmitted.
    $json = $this->getJson($entity);

    // If nothing is returned then just fail.
    if (empty($json)) {
      return;
    }

    // Build and save the log record.
    $log_entry = [
      'entity_uuid' => $entity->uuid(),
      'title' => $entity->label(),
      'type' => $entity->getEntityTypeId(),
      'bundle' => $entity->bundle(),
      'langcode' => $entity->language()->getId(),
      'published' => (method_exists($entity, 'isPublished') && $entity->isPublished()),
      'json' => json_encode($json),
    ];
    $log = $this->entityTypeManager->getStorage('preview_log_entity')
      ->create($log_entry);
    $log->save();
  }

  /**
   * Deletes existing entities based on uuid.
   *
   * @param string $uuid
   *   The entity uuid to delete the log entries for.
   * @param int $uid
   *   The entity user id.
   * @param string $langcode
   *   The entity language code.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteLoggedEntity(string $uuid, int $uid, string $langcode = 'en'): void {
    $query = $this->entityTypeManager->getStorage('preview_log_entity')->getQuery()->accessCheck(FALSE);
    $entity_uuids = $query
      ->condition('entity_uuid', $uuid)
      ->condition('uid', $uid)
      ->condition('langcode', $langcode)
      ->execute();
    $entities = $this->entityTypeManager
      ->getStorage('preview_log_entity')
      ->loadMultiple($entity_uuids);

    foreach ($entities as $entity) {
      $entity->delete();
    }
  }

  /**
   * Deletes old or expired existing logged entities based on timestamp.
   *
   * @param int $timestamp
   *   The entity uuid to delete the log entries for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteExpiredLoggedEntities(int $timestamp): void {
    $query = \Drupal::entityQuery('preview_log_entity')
      ->condition('created', $timestamp, '<')
      ->range(0, 50)
      ->accessCheck(FALSE);
    $ids = $query->execute();

    foreach ($ids as $id) {
      $entity = $this->entityTypeManager->getStorage('preview_log_entity')
        ->load($id);
      $entity->delete();
    }
  }

  /**
   * Gets the JSON object for an entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to JSONify.
   *
   * @return array|null
   *   The array of json data or NULL in the case of a failed request.
   */
  public function getJson(ContentEntityInterface $entity): ?array {
    $json = [];
    $resource_type = $this->entityToJsonApiPreview->getResourceType($entity->getEntityTypeId(), $entity->bundle());

    if (empty($resource_type)) {
      return $json;
    }

    $included = [];
    $includes = $this->config->get('includes');
    if (!empty($includes) && !empty($includes[$entity->bundle()])) {
      $bundle_includes = $includes[$entity->bundle()];
      $exploded = explode(',', $bundle_includes);
      foreach ($exploded as $include) {
        $path_parts = explode('.', $include);
        if ($this->entityToJsonApiPreview->isValidInclude($resource_type, $path_parts)) {
          $included[] = $include;
        }
      }
    }

    try {
      $json_request = $this->entityToJsonApiPreview->normalize($entity, $included);
      $json = [
        'data' => $json_request['data'],
      ];
      if (isset($json_request['included'])) {
        $json['included'] = $json_request['included'];
      }
    }
    catch (RouteNotFoundException $e) {
      return NULL;
    }

    return $json;
  }

}
