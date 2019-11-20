<?php

namespace Drupal\falcon_gdpr_commerce\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\falcon_gdpr\Anonymiser;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Individual order anonymisation actions.
 */
class OrderAnonymiserForm extends FormBase {

  /**
   * Commerce anonymiser.
   *
   * @var \Drupal\falcon_gdpr\Anonymiser
   */
  protected $anonymiser;

  /**
   * Current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('falcon_gdpr.anonymiser'),
      $container->get('current_route_match')
    );
  }

  /**
   * OrderAnonymiserForm constructor.
   *
   * @param \Drupal\falcon_gdpr\Anonymiser $anonymiser
   *   Commerce anonymiser.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Route match service.
   */
  public function __construct(Anonymiser $anonymiser, RouteMatchInterface $route_match) {
    $this->anonymiser = $anonymiser;
    // Grab current order from URL.
    $this->order = $route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'falcon_gdpr_commerce_order';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->order->get('field_gdpr_anonymised')->isEmpty()) {
      $this->messenger()->addStatus($this->t('This order has been anonymised.'));
      $form['anonymised_time'] = $this->order->get('field_gdpr_anonymised')->view();
      return $form;
    }

    $not_pushed_in_crm = $this->order->hasField('field_crm_sync_timestamp') && $this->order->get('field_crm_sync_timestamp')->isEmpty();
    if ($this->order->getState()->getId() === 'completed' && $not_pushed_in_crm) {
      $this->messenger()->addWarning($this->t('This order has not been pushed to CRM yet.'));
      return $form;
    }

    $form['actions'] = [
      '#type' => 'fieldset',
    ];
    $form['actions']['process_button'] = [
      '#type' => 'submit',
      '#value' => 'Anonymise now',
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->anonymiser->processEntity($this->order)) {
      $this->messenger()->addStatus($this->t('Anonymisation successfully completed.'));
    }
    else {
      $this->messenger()->addError($this->t("The order hasn't been processed due to an error. Please inform technical support."));
    }
  }

}
