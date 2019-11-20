<?php

namespace Drupal\falcon_gdpr;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * A plugin manager for FalconNfp365Order plugins.
 */
class EntityAnonymiserPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // Defines plugin directory.
    $subdir = 'Plugin/Falcon/EntityAnonymiser';

    parent::__construct($subdir, $namespaces, $module_handler, 'Drupal\falcon_gdpr\EntityAnonymiserPluginInterface', 'Drupal\falcon_gdpr\Annotation\FalconEntityAnonymiser');

    // This allows the plugin definitions to be altered by an alter hook.
    $this->alterInfo('falcon_gdpr_entity_anonymiser_info');

    // This sets the caching method for plugin definitions.
    $this->setCacheBackend($cache_backend, 'falcon_gdpr_entity_anonymiser_info');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    if (empty($definition['type'])) {
      throw new PluginException(sprintf('Entity anonymiser plugin %s must define the "entity_type" property.', $plugin_id));
    }
  }

}
