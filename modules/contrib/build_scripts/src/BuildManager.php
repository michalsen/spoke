<?php

namespace Drupal\build_scripts;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;

/**
 * Build manager contains functions for IPC communication.
 *
 * Build manager is implemented by communicating with external service. This
 * design was chosen so that the web server process does not have to have
 * permissions required to build the site. The external service has its
 * own permissions and only a limited API is exposed.
 */
class BuildManager implements BuildManagerInterface {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * Construct the BuildManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Drupal configuration factory.
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   Helper class to construct a HTTP client with Drupal specific config.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $client_factory) {
    $this->config = $config_factory->get('build_scripts.settings');
    $this->client = $client_factory->fromOptions([
      'base_uri' => $this->config->get('address'),
      'http_errors' => FALSE,
      'headers' => [
        'Host' => \Drupal::request()->getHost(),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function start($stage, $language) {
    if (!$this->isValidStage($stage)) {
      throw new \Exception("Stage {$stage} is not one of the configured stages.");
    }

    try {
      $response = $this->client->post("/start/{$stage}+{$language}");
    }
    catch (RequestException $e) {
      return NULL;
    }

    return json_decode($response->getBody());
  }

  /**
   * {@inheritdoc}
   */
  public function streamLogs($build_id) {
    try {
      $response = $this->client->get("/logs/by-id/{$build_id}", [
        'stream' => TRUE,
      ]);
    }
    catch (RequestException $e) {
      return new BuildLogStream(NULL);
    }

    return new BuildLogStream($response);
  }

  /**
   * Validate stage parameter.
   *
   * @return bool
   *   True if stage is valid.
   */
  private function isValidStage($stage) {
    return in_array($stage, $this->config->get('stages'));
  }

}
