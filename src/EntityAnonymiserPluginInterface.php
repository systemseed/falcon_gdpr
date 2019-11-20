<?php

namespace Drupal\falcon_gdpr;

use Drupal\Core\Entity\EntityInterface;

/**
 * EntityAnonymiserPluginInterface interface.
 */
interface EntityAnonymiserPluginInterface {

  /**
   * Handles anonymisation of given content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to anonymise.
   *
   * @throws \Exception
   *   Throws exception if entity can't be processed.
   */
  public function process(EntityInterface $entity);

  /**
   * Checks if the plugin supports processing of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to anonymise.
   *
   * @return bool
   *   TRUE if the plugin can process the given entity.
   */
  public function isEntitySupported(EntityInterface $entity);

}
