<?php

namespace Drupal\simple_decoupled_preview_jsonapi\Routing;

use Drupal\jsonapi\ParamConverter\ResourceTypeConverter;
use Drupal\jsonapi\Routing\Routes as JsonApiRoutes;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes for node previews.
 *
 * @see \Drupal\jsonapi\Routing\Routes
 */
class Routes extends JsonApiRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = new RouteCollection();

    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      $entity_type_id = $resource_type->getEntityTypeId();
      if ($entity_type_id === 'node') {
        $path = $resource_type->getPath();
        $preview_route = new Route("/$path/{node_preview}/preview");
        $preview_route->addDefaults([RouteObjectInterface::CONTROLLER_NAME => 'simple_decoupled_preview_jsonapi.entity_resource:getIndividualNodePreview']);
        $preview_route->setMethods(['GET']);
        $preview_route->setRequirement('_node_preview_access', '{node_preview}');
        // Add node_preview parameter conversion instead of JSON:API default
        // entity conversion.
        $preview_route->addOptions(['parameters' => ['node_preview' => ['type' => 'node_preview']]]);
        static::addRouteParameter($preview_route, static::RESOURCE_TYPE_KEY, ['type' => ResourceTypeConverter::PARAM_TYPE_ID]);
        $preview_route->addDefaults([static::RESOURCE_TYPE_KEY => $resource_type->getTypeName()]);
        $routes->add(static::getRouteName($resource_type, 'individual.preview'), $preview_route);
      }
    }

    // Resource routes all have the same base path.
    $routes->addPrefix($this->jsonApiBasePath);

    // Require the JSON:API media type header on every route.
    $routes->addRequirements(['_content_type_format' => 'api_json']);

    // Enable all available authentication providers.
    $routes->addOptions(['_auth' => $this->providerIds]);

    // Flag every route as belonging to the JSON:API module.
    $routes->addDefaults([static::JSON_API_ROUTE_FLAG_KEY => TRUE]);

    // All routes serve only the JSON:API media type.
    // This is the only "required" requirement (forgive the redundancy) to make
    // JSON:API return its own 404 error (in JSON format) instead of Drupal's
    // default 404 HTML page.
    $routes->addRequirements(['_format' => 'api_json']);

    return $routes;
  }

}
