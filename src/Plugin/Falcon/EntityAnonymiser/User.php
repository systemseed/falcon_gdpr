<?php

namespace Drupal\falcon_gdpr\Plugin\Falcon\EntityAnonymiser;

use Drupal\Core\Entity\EntityInterface;
use Drupal\falcon_gdpr\EntityAnonymiserPluginBase;

/**
 * User anonymisation plugin.
 *
 * @FalconEntityAnonymiser(
 *   id = "falcon_gdpr_user",
 *   description = @Translation("Anonymiser for User entity type."),
 *   type = "user"
 * )
 */
class User extends EntityAnonymiserPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity) {
    // There is no useful data on user level so we just delete it.
    $entity->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function isEntitySupported(EntityInterface $entity) {
    /* @var \Drupal\user\UserInterface $entity */
    if (!parent::isEntitySupported($entity)) {
      return FALSE;
    }

    $allowed_roles = $this->config->get('user_roles');
    // Apply user roles configuration.
    foreach ($entity->getRoles() as $role_id) {
      if (!in_array($role_id, $allowed_roles)) {
        return FALSE;
      }
    }

    // Do not process users with orders. Orders must be either deleted or
    // anonymised first.
    $query = $this->entityTypeManager->getStorage('commerce_order')->getQuery();
    $count = $query
      ->condition('uid', $entity->id())
      ->notExists('field_gdpr_anonymised')
      ->count()
      ->execute();

    if ($count) {
      $this->logger->info('Skipping anonymisation of a user %email - there are @count orders associated with this user.', [
        '%email' => $entity->getEmail(),
        '@count' => $count,
      ]);
      return FALSE;
    }

    // Prevent user removal if a user has ever logged in.
    if ($entity->getLastLoginTime()) {
      $this->logger->error('Attempt to anonymise user %email was blocked because the user has non-empty last login time. Please review the user and either add missing roles or cancel user account manually.', [
        '%email' => $entity->getEmail(),
        'link' => $entity->toLink('Edit user', 'edit-form')->toString(),
      ]);

      return FALSE;
    }

    return TRUE;
  }

}
