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
  public function process(EntityInterface $profile) {
    // Clean up fields that can contain personal data.
    $fields_to_reset = [
      'address',
      'field_phone',
      'field_profile_address',
      'field_profile_email',
      'field_profile_first_name',
      'field_profile_phone',
      'field_profile_title',
      'field_title',
    ];
    /* @var \Drupal\profile\Entity\ProfileInterface $profile */
    foreach ($fields_to_reset as $field_name) {
      if ($profile->hasField($field_name) && !$profile->get($field_name)->isEmpty()) {
        $profile->{$field_name} = NULL;
      }
    }
    // Assign a profile to anonymous user so it will not be deleted if owner
    // is deleted.
    $profile->setOwnerId(0);

    $profile->save();
  }
}
