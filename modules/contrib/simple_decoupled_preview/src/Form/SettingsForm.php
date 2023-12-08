<?php

namespace Drupal\simple_decoupled_preview\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simple_decoupled_preview_jsonapi\EntityToJsonApiPreview;

/**
 * Defines a config form to store preview configuration.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\simple_decoupled_preview_jsonapi\EntityToJsonApiPreview
   */
  protected $entityToJsonApiPreview;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entityTypeManager,
    ModuleHandlerInterface $moduleHandler,
    EntityToJsonApiPreview $entity_to_json_api_preview
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
    $this->entityToJsonApiPreview = $entity_to_json_api_preview;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('simple_decoupled_preview.entity_to_jsonapi_preview'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_decoupled_preview.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_decoupled_preview_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simple_decoupled_preview.settings');
    $includes = !empty($config->get('includes')) ? array_intersect_key($config->get('includes'), $this->getNodeTypes()) : [];
    $bundles = !empty($config->get('bundles')) ? array_intersect_key($config->get('bundles'), $this->getNodeTypes()) : [];

    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Preview settings'),
      '#open' => TRUE,
      '#description' => $this->t('Specify a page on your decoupled site
      that will be used to generate the iframe. Arguments for {node.bundle} and
      {node.uuid} will be appended to the iframe url path for the node you are
      previewing. You can use these arguments to make api requests to Drupal to
      pull the preview log entity into your template and generate the JSON.
      '),
    ];
    $form['preview']['preview_callback_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Preview Iframe URL'),
      '#description' => $this->t('<p>The URL to the decoupled server and 
      path to preview page.</p>'),
      '#default_value' => $config->get('preview_callback_url'),
      '#maxlength' => 250,
      '#field_suffix' => '/{bundle}/{uuid}/{langcode}/{uid}'
    ];
    $form['preview']['example'] = [
      '#type' => 'fieldset',
      '#markup' => '
      <table>
        <thead>
          <th>Environment</th>
          <th>Example URL</th>
        </thead>
        <tbody>
          <tr>
            <td>Local development</td>
            <td><code>http://localhost:8000/preview</code></td>
          </tr>
          <tr>
            <td>Production</td>
            <td><code>https://www.example/preview</code></td>
          </tr>
        </tbody>
      </table>
      '
    ];
    $form['preview']['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Node Types'),
      '#description' => $this->t('Select the Node types to log previews for and enable iframe display.'),
      '#options' => $this->getNodeTypes(),
      '#default_value' => $bundles,
    ];
    $form['includes'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('JSON:API Includes'),
      '#description' => $this->t('<p>Specify relationship paths to 
      include in the response document. Includes can be chained to account for
      nested relationships. Use the field public name aka (Alias) in the case of
      modifications made with the <em>JSON:API Extras</em> module.
      </p>
      <strong>Example</strong>: <code>field_media.field_media_image</code>
      '),
      '#tree' => TRUE,
    ];
    foreach ($this->getNodeTypes() as $machine_name => $nodeType) {
      $form['includes'][$machine_name] = [
        '#type' => 'textarea',
        '#title' => $this->t('@label includes', ['@label' => $nodeType]),
        '#rows' => 2,
        '#description' => $this->t('Enter includes separated by a comma for the %node_type node type.', ['%node_type' => $machine_name]),
        '#default_value' => $includes[$machine_name] ?? '',
        '#element_validate' => [[$this, 'validateIncludes']],
      ];
    }
    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['advanced']['delete_log_entities'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete Old Preview Log Entities'),
      '#description' => $this->t('Enable this to automatically clean up old
        Preview log entities on cron runs.'),
      '#default_value' => $config->get('delete_log_entities') ?? TRUE,
    ];
    $form['advanced']['log_expiration'] = [
      '#type' => 'select',
      '#title' => $this->t('Previews Log Expiration'),
      '#description' => $this->t('How long do you want to store the Preview
        log entities (after this time they will be automatically deleted)?'),
      // Expiration values are stored in seconds.
      '#options' => [
        '86400' => $this->t('1 day'),
        '259200' => $this->t('3 days'),
        '604800' => $this->t('7 days'),
        '1209600' => $this->t('14 days'),
        '2592000' => $this->t('30 days'),
        '5184000' => $this->t('60 days'),
        '7776000' => $this->t('90 days'),
      ],
      '#default_value' => $config->get('log_expiration'),
      '#states' => [
        'visible' => [
          ':input[name="delete_log_entities"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @param array $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return void
   */
  public function validateIncludes(array $element, FormStateInterface &$form_state): void {
    $value = $element['#value'];
    if (!empty($value)) {
      $type = array_pop($element['#parents']);
      $resource_type = $this->entityToJsonApiPreview->getResourceType('node', $type);
      $includes = explode(',', $value);
      $errors = [];
      foreach ($includes as $include) {
        $paths = explode('.', $include);
        if (!$this->entityToJsonApiPreview->isValidInclude($resource_type, $paths)) {
          $errors[] = $include;
        }
      }
      if (!empty($errors)) {
        $form_state->setErrorByName($element['#name'], t('The include path at %includes is invalid for %name.',
          [
            '%includes' => implode(',', $errors),
            '%name' => $element['#title'],
          ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $bundles = array_filter($form_state->getValue('bundles'));

    $this->config('simple_decoupled_preview.settings')
      ->set('preview_callback_url', $form_state->getValue('preview_callback_url'))
      ->set('bundles', $bundles)
      ->set('includes', $form_state->getValue('includes'))
      ->set('delete_log_entities', $form_state->getValue('delete_log_entities'))
      ->set('log_expiration', $form_state->getValue('log_expiration'))
      ->save();

    if (!empty($bundles)) {
      $preview_display = 'decoupled_preview';
      $display_repository = \Drupal::service('entity_display.repository');
      $needs_enabled = [];
      foreach ($bundles as $bundle) {
        $active_displays = $display_repository->getViewModeOptionsByBundle('node', $bundle);
        if (!array_key_exists($preview_display, $active_displays)) {
          $needs_enabled[] = $bundle;
        }
      }
      if (!empty($needs_enabled)) {
        \Drupal::messenger()->addWarning(
          $this->t('The %display display is currently disabled for node 
          types: %bundles.',
            [
              '%display' => 'Decoupled Preview',
              '%bundles' => implode(', ', $needs_enabled)
            ]
          )
        );
      }
    }
  }

  /**
   * Gets a list of all the defined content entities in the system.
   *
   * @return array
   *   An array of content entities definitions.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getNodeTypes(): array {
    // Get all node types.
    $node_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $types = [];

    foreach ($node_types as $key => $node_type) {
      $types[$key] = $node_type->label();
    }
    return $types;
  }

}
