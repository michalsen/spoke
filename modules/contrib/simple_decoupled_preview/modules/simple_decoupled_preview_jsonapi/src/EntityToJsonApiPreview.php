<?php

namespace Drupal\simple_decoupled_preview_jsonapi;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Simplifies the process of generating a JSON:API preview version of an entity.
 *
 * @api
 */
class EntityToJsonApiPreview {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The JSON:API Resource Type Repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * A Session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $currentRequest;

  /**
   * EntityToJsonApi constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The resource type repository.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The stack of requests.
   */
  public function __construct(
    HttpKernelInterface $http_kernel,
    ResourceTypeRepositoryInterface $resource_type_repository,
    SessionInterface $session,
    RequestStack $request_stack
  ) {
    $this->httpKernel = $http_kernel;
    $this->resourceTypeRepository = $resource_type_repository;
    $this->currentRequest = $request_stack->getCurrentRequest();
    $this->session = $this->currentRequest->hasPreviousSession()
      ? $this->currentRequest->getSession()
      : $session;
  }

  /**
   * Return the requested entity as a raw string.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   * @param string[] $includes
   *   The list of includes.
   *
   * @return string
   *   The raw JSON string of the requested resource.
   *
   * @throws \Exception
   */
  public function serialize(EntityInterface $entity, array $includes = []) {
    $resource_type = $this->resourceTypeRepository->get($entity->getEntityTypeId(), $entity->bundle());
    $path = $resource_type->getPath();
    $route_name = '/jsonapi' . $path . '/' . $entity->uuid() . '/preview';
    $query = [];
    if ($includes) {
      $query = ['include' => implode(',', $includes)];
    }
    $request = Request::create(
      $route_name,
      'GET',
      $query,
      $this->currentRequest->cookies->all(),
      [],
      $this->currentRequest->server->all()
    );
    if ($this->session) {
      $request->setSession($this->session);
    }
    $response = $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST);
    return $response->getContent();
  }

  /**
   * Return the requested entity as an structured array.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to generate the JSON from.
   * @param string[] $includes
   *   The list of includes.
   *
   * @return array
   *   The JSON structure of the requested resource.
   *
   * @throws \Exception
   */
  public function normalize(EntityInterface $entity, array $includes = []) {
    return Json::decode($this->serialize($entity, $includes));
  }

  /**
   * Return the jsonapi resource type.
   *
   * @param string $type
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\jsonapi\ResourceType\ResourceType
   *   The jsonapi ResourceType.
   */
  public function getResourceType(string $type, string $bundle): ResourceType {
    return $this->resourceTypeRepository->get($type, $bundle);
  }

  /**
   * Validates an include string by checking each path.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   The jsonapi ResourceType.
   * @param array $path_parts
   *   An array of paths exploded by "." separator from include string.
   *
   * @return bool
   *   Returns TRUE or FALSE depending on whether all include paths are valid.
   */
  public function isValidInclude(ResourceType $resource_type, array $path_parts): bool {
    if (empty($path_parts)) {
      return FALSE;
    }
    $public_name = $path_parts[0];
    $field = $resource_type->getFieldByPublicName($public_name);
    $remaining_parts = array_slice($path_parts, 1);
    if (!empty($remaining_parts)) {
      $relatable_resource_types = $resource_type->getRelatableResourceTypesByField($public_name);
      foreach ($relatable_resource_types as $relatable_resource_type) {
        if ($this->isValidInclude($relatable_resource_type, $remaining_parts)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    return $field && $field->isFieldEnabled();
  }

}
