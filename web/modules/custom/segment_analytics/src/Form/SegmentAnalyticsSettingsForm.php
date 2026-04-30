<?php

namespace Drupal\segment_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for segment Integration.
 */
class SegmentAnalyticsSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   */
  protected const string SETTINGS = 'segment_analytics.settings';

  /**
   * {@inheritDoc}
   */
  public function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'segment_analytics_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['write_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Writen Key'),
      '#description' => $this->t('The write key is a unique identifier for each source. It lets Segment know which source is sending <br> the data and which destinations should receive that data.'),
      '#default_value' => $config->get('write_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config(static::SETTINGS)
      ->set('write_key', $form_state->getValue('write_key'))
      ->save();
  }

}
