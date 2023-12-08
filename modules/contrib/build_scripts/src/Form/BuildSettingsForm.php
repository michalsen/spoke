<?php

namespace Drupal\build_scripts\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure OE builder settings for this site.
 */
class BuildSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'build_scripts_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['build_scripts.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('build_scripts.settings');

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Builder address'),
      '#required' => TRUE,
      '#default_value' => $config->get('address'),
    ];

    // Implement "Add more" widget:
    // https://drupal.stackexchange.com/a/290222/96997.
    $build_stages = $form_state->get('build_stages');
    $saved_stages = $config->get('stages');

    // We have to ensure that there is at least one field.
    // This implicates we are building the form for the first time.
    if ($build_stages === NULL) {
      $build_stages = count($saved_stages) > 0 ? count($saved_stages) : 1;
      $form_state->set('build_stages', $build_stages);
    }

    $form['#tree'] = TRUE;
    $form['stages_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Build stages'),
      '#prefix' => '<div id="stages-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $build_stages; $i++) {
      $form['stages_fieldset']['stage'][$i] = [
        '#type' => 'textfield',
        '#title' => $this->t('Stage name'),
      ];

      // Retrive previously saved value.
      if (array_key_exists($i, $saved_stages)) {
        $form['stages_fieldset']['stage'][$i]['#default_value'] = $saved_stages[$i];
      }
    }

    $form['stages_fieldset']['actions'] = [
      '#type' => 'actions',
    ];
    $form['stages_fieldset']['actions']['add_stage'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add stage'),
      '#submit' => ['::addOne'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'stages-fieldset-wrapper',
      ],
    ];

    // If there is more than one name, add the remove button.
    if ($build_stages > 1) {
      $form['stages_fieldset']['actions']['remove_stage'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'stages-fieldset-wrapper',
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $stages = $form_state->getValue(['stages_fieldset', 'stage']);

    $this->config('build_scripts.settings')
      ->set('stages', array_filter($stages, 'strlen'))
      ->save();

    $this->config('build_scripts.settings')
      ->set('address', $form_state->getValue('address'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the stages in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['stages_fieldset'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('build_stages');
    $add_button = $name_field + 1;
    $form_state->set('build_stages', $add_button);
    // Since our buildForm() method relies on the value of 'build_stages' to
    // generate 'stage' form elements, we have to tell the form to rebuild. If
    // we don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('build_stages');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('build_stages', $remove_button);
    }
    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $form_state->setRebuild();
  }

}
