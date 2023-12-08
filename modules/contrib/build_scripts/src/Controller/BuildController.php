<?php

namespace Drupal\build_scripts\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Drupal\Core\Url;
use Drupal\build_scripts\BuildManagerInterface;
use Drupal\build_scripts\BuildLogStreamInterface;

/**
 * Handles site building.
 */
class BuildController extends ControllerBase {

  /**
   * Read buffer in bytes.
   */
  const READ_BUFFER = 128;

  /**
   * Build timeout in seconds.
   */
  const BUILD_TIMEOUT = 60 * 10;

  /**
   * The build manager.
   *
   * @var \Drupal\build_scripts\BuildManagerInterface
   */
  private $buildManager;

  /**
   * Constructs a BuildController object.
   *
   * @param \Drupal\build_scripts\BuildManagerInterface $build_manager
   *   The module handler service.
   */
  public function __construct(BuildManagerInterface $build_manager) {
    $this->buildManager = $build_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('build_scripts.build_manager')
    );
  }

  /**
   * Returns a page title.
   */
  public function getTitle($stage) {
    return $this->t("Building stage @stage", [
      '@stage' => $stage,
    ]);
  }

  /**
   * View controlling page.
   */
  public function view(Request $request, $stage) {
    $session = $request->getSession();
    $buildId = $session->get("build_scripts.{$stage}");

    $build['output'] = [
      '#type' => 'html_tag',
      '#tag' => 'code',
      '#value' => $this->t('Loading build logs'),
      '#attributes' => [
        'class' => 'builder',
        'id' => 'builder-output',
      ],
    ];

    if (!empty($buildId)) {
      $endpoint = Url::fromRoute('build_scripts.logs', ['build_id' => $buildId])->toString();

      // Add javascript that will stream build log to #builder-output element.
      $build['#attached'] = [
        'library' => ['build_scripts/build_scripts.builder'],
        'drupalSettings' => [
          'build_scripts' => [
            'buildId' => $buildId,
            'endpoint' => $endpoint,
          ],
        ],
      ];

    }
    else {
      \Drupal::messenger()->addMessage(t('Build failed to start'), 'error');
    }

    return $build;
  }

  /**
   * Starts build.
   */
  public function start(Request $request, $stage) {
    $language = $this->languageManager()->getCurrentLanguage()->getId();
    $buildId = $this->buildManager->start($stage, $language);

    $session = $request->getSession();

    \Drupal::logger('build_scripts')->info('Build job \'@buildId\' started', [
      '@buildId' => $buildId,
    ]);

    if (!empty($buildId)) {
      // Save build id to session.
      $session->set("build_scripts.{$stage}", $buildId);

      return new JsonResponse($buildId);
    }

    throw new ServiceUnavailableHttpException();
  }

  /**
   * Stream build logs.
   */
  public function logs(Request $request, $build_id) {
    // Limits (hopefully increases) the maximum execution time.
    // WARNING: This function has no effect when PHP is running in safe mode.
    set_time_limit(self::BUILD_TIMEOUT);

    $logstream = $this->buildManager->streamLogs($build_id);

    // :( No content. This means that build has completed but logs are already
    // truncated.
    if ($logstream->getStatusCode() == BuildLogStreamInterface::STATUS_UNAVAILABLE) {
      throw new ServiceUnavailableHttpException();
    }

    $response = new StreamedResponse(function () use ($logstream) {
      // There are multiple layers buffering the request.
      // We are trying to achieve good latency with streaming.
      // Clean (erase) the output buffer and turn off output buffering.
      ob_end_clean();

      $handle = fopen('php://output', 'r+');

      while (!$logstream->eof()) {
        $chunk = $logstream->read(self::READ_BUFFER);

        // Copy received data to StreamedResponse.
        fwrite($handle, $chunk);

        // Minimize buffering on PHP. Trust that the underlying layer has done
        // adequate buffering.
        flush();
      }

      fclose($handle);
    });

    // Disables FastCGI buffering in nginx for this response.
    $response->headers->set('X-Accel-Buffering', 'no');
    $response->headers->set('Content-Type', 'text/plain');
    $response->setStatusCode($logstream->getStatusCode());

    return $response;
  }

}
