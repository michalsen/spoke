<?php

namespace Drupal\simple_decoupled_preview\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\simple_decoupled_preview\Entity\PreviewLogEntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a resource for retrieving simple decoupled preview json data.
 *
 * @RestResource(
 *   id = "simple_decoupled_preview_json",
 *   label = @Translation("Simple Decoupled Preview JSON"),
 *   uri_paths = {
 *     "canonical" = "/api/preview/{uuid}"
 *   }
 * )
 */
class PreviewResource extends ResourceBase {

  /**
   * The entity type manager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->request = $container->get('request_stack');
    return $instance;
  }

  /**
   * Returns preview entity json data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP request object.
   * @param string $uuid
   *   Preview Log Entity UUID.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   HTTP response object containing preview log entity json.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get(Request $request, string $uuid): ModifiedResourceResponse {
    if (empty($uuid)) {
      $errors = [
        'error' => [
          'message' => $this->t('The preview entity UUID is required.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }

    $params = $request->query->all();
    $langcode = $params['langcode'] ?? 'en';
    $uid = $params['uid'] ?? NULL;

    if (empty($uid)) {
      $errors = [
        'error' => [
          'message' => $this->t('Missing required parameter: uid'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }

    $entity = $this->entityTypeManager->getStorage('preview_log_entity')
      ->loadByProperties([
        'entity_uuid' => $uuid,
        'langcode' => $langcode,
        'uid' => $uid,
      ]);

    // Load the preview entity.
    if (empty($entity)) {
      $errors = [
        'error' => [
          'message' => $this->t('Invalid preview entity UUID.'),
        ],
      ];
      return new ModifiedResourceResponse($errors, 400);
    }
    $entity = reset($entity);

    if ($entity instanceof PreviewLogEntityInterface) {

      $response = json_decode($entity->get('json')->value, TRUE);

      return new ModifiedResourceResponse($response);
    }

    throw new NotFoundHttpException(t("Can't load preview entity."));

  }

}
