<?php

namespace Drupal\falcon_gdpr\Plugin\Falcon\EntityAnonymiser;

use Drupal\Core\Entity\EntityInterface;
use Drupal\falcon_gdpr\EntityAnonymiserPluginBase;

/**
 * Order anonymisation plugin.
 *
 * @FalconEntityAnonymiser(
 *   id = "falcon_gdpr_commerce_order",
 *   description = @Translation("Anonymiser for Commerce Order entity type."),
 *   type = "commerce_order"
 * )
 */
class CommerceOrder extends EntityAnonymiserPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $order) {
    /* @var \Drupal\user\UserInterface $customer */
    $customer = $order->getCustomer();

    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $delete_mode = $order->getState()->getId() === 'draft';

    // Fields that should be cleaned up on order level.
    $fields_to_reset = [
      'ip_address',
      'uid',
      'payment_method',
      'field_crm_metadata',
    ];

    // Collection of all sub-entities that should be either deleted or updated.
    $entities_to_delete = [];
    $entities_to_update = [];

    // Handle payments and payment methods.
    if (!$order->get('payment_method')->isEmpty()) {
      /* @var \Drupal\commerce_payment\PaymentStorageInterface $paymentStorage */
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

      foreach ($payment_storage->loadMultipleByOrder($order) as $payment) {
        /* @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        if ($payment_method = $payment->getPaymentMethod()) {
          // Payment method contains sensitive information so we mark it for
          // removal.
          $entities_to_delete[] = $payment_method;
          // Payment itself doesn't contain sensitive information and can be
          // kept for reference.
          if (!$payment_method->getRemoteId() && !$payment->getRemoteId()) {
            $remote_id_combined = '';
          }
          else {
            $remote_id_combined = $payment_method->getRemoteId() . ' / ' . $payment->getRemoteId();
          }
          $payment->setRemoteId($remote_id_combined);
          $entities_to_update[] = $payment;
        }
      }

    }

    // Handle billing profile.
    $email_placeholder = '- Anonymised - / Last name: ';
    if ($profile = $order->getBillingProfile()) {
      // Grab customer's last name before anonymising billing profile.
      $billing_address = $profile->get('address')->first();
      if (!empty($billing_address)) {
        $email_placeholder .= $billing_address->getFamilyName();
      }

      if ($delete_mode) {
        $entities_to_delete[] = $profile;
      }
      else {
        $this->anonymiser->processEntity($profile);
      }

    }

    if ($delete_mode) {
      // In draft mode delete everything.
      $entities_to_delete = array_merge($entities_to_delete, $entities_to_update);
      $entities_to_delete[] = $order;

      $entities_to_update = [];
    }
    else {
      $order->setEmail($email_placeholder);
      foreach ($fields_to_reset as $field_name) {
        if ($order->hasField($field_name) && !$order->get($field_name)->isEmpty()) {
          $order->{$field_name} = NULL;
        }
      }
      $entities_to_update[] = $order;
    }

    // Perform all database operations as a single transaction.
    $db_transaction = $this->database->startTransaction('falcon_gdpr_commerce_' . $order->id());
    try {
      foreach ($entities_to_delete as $entity) {
        /* @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity->delete();
      }
      foreach ($entities_to_update as $entity) {
        /* @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity->save();
      }
    }
    catch (\Exception $e) {
      $db_transaction->rollBack();
      throw $e;
    }

    if (!$customer->isAnonymous()) {
      $this->anonymiser->processEntity($customer);
    }
  }

}
