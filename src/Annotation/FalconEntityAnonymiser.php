<?php

namespace Drupal\falcon_gdpr\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FalconEntityAnonymiser annotation object.
 *
 * @Annotation
 */
class FalconEntityAnonymiser extends Plugin {

  /**
   * Supported entity type.
   *
   * @var string
   */
  public $type;

}
