<?php

namespace Drupal\falcon_gdpr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Falcon GDPR settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'falcon_gdpr_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['falcon_gdpr.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('falcon_gdpr.settings');

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('GDPR anonymisation settings'),
      '#open' => TRUE,
    ];
    $form['settings']['threshold_strtotime'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit automated entities anonymisaiton by date'),
      '#description' => $this->t('String in <code>strtotime()</code> format, for example, "first day of -3 months". Note that different anonymisation modules can treat this setting differently.'),
      '#required' => TRUE,
      '#default_value' => $config->get('threshold_strtotime'),
    ];

    $user_roles_options = user_role_names(TRUE);
    $form['settings']['user_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose user roles that can be anonymised'),
      '#description' => $this->t('Users with these roles will be modified/removed by anonymiser. Other users will not be touched.'),
      '#options' => $user_roles_options,
      '#default_value' => $config->get('user_roles') ? $config->get('user_roles') : [],
    ];

    $form['settings']['entities_per_cron'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of entities to process per cron run'),
      '#required' => TRUE,
      '#default_value' => $config->get('entities_per_cron') ? $config->get('entities_per_cron') : 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('falcon_gdpr.settings')
      ->set('threshold_strtotime', $form_state->getValue('threshold_strtotime'))
      ->set('user_roles', array_filter($form_state->getValue('user_roles')))
      ->set('entities_per_cron', $form_state->getValue('entities_per_cron'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
