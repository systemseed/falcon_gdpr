<?php

namespace Drupal\falcon_gdpr;

use Drupal\Core\Entity\EntityInterface;

/**
 * Falcon Anonymiser interface.
 */
interface AnonymiserInterface {

  /**
   * Processes one entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to anonymise.
   *
   * @return bool
   *   TRUE if the entity was anonymised.
   */
  public function processEntity(EntityInterface $entity);

}
