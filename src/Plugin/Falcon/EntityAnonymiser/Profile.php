<?php

namespace Drupal\falcon_gdpr\Plugin\Falcon\EntityAnonymiser;

use Drupal\Core\Entity\EntityInterface;
use Drupal\falcon_gdpr\EntityAnonymiserPluginBase;

/**
 * Profile anonymisation plugin.
 *
 * @FalconEntityAnonymiser(
 *   id = "falcon_gdpr_profile",
 *   description = @Translation("Anonymiser for Profile."),
 *   type = "profile"
 * )
 */
class Profile extends EntityAnonymiserPluginBase {

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity) {
    // Clean up fields that can contain personal data.
    $fields_to_reset = [
      'address',
      'field_phone',
      'field_profile_address',
      'field_profile_email',
      'field_profile_first_name',
      'field_profile_phone',
      'field_profile_title'
    ];
    /* @var \Drupal\profile\Entity\ProfileInterface $entity */
    foreach ($fields_to_reset as $field_name) {
      if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
        $entity->{$field_name} = NULL;
      }
    }

    $entity->save();
  }
}
