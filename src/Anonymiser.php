<?php

namespace Drupal\falcon_gdpr;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Falcon Anonymiser service.
 */
class Anonymiser implements AnonymiserInterface {

  use StringTranslationTrait;

  /**
   * Entity Type manager used to retrieve field storage info.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The config for Falcon GDPR module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Plugin Manager for entity anonymisers.
   *
   * @var \Drupal\falcon_gdpr\EntityAnonymiserPluginManager
   */
  protected $anonymiserPluginManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    EntityAnonymiserPluginManager $anonymiser_plugin_manager,
    LoggerInterface $logger,
    TimeInterface $time
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('falcon_gdpr.settings');
    $this->anonymiserPluginManager = $anonymiser_plugin_manager;
    $this->logger = $logger;
    $this->time = $time;
  }

  /**
   * Finds entity anonymiser plugin for that can process the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to anonymise.
   *
   * @return bool|\Drupal\falcon_gdpr\EntityAnonymiserPluginInterface
   *   Plugin instance or FALSE if the given entity can't be processed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Error during plugin lookup.
   */
  protected function getPluginByEntity(EntityInterface $entity) {
    $plugin_definitions = $this->anonymiserPluginManager->getDefinitions();

    foreach ($plugin_definitions as $plugin_name => $plugin_definition) {
      if ($entity->getEntityTypeId() === $plugin_definition['type']) {
        /* @var \Drupal\falcon_gdpr\EntityAnonymiserPluginInterface $plugin */
        $plugin = $this->anonymiserPluginManager->createInstance($plugin_name);
        if ($plugin->isEntitySupported($entity)) {
          return $plugin;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function processEntity(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    try {
      if ($anonymiser = $this->getPluginByEntity($entity)) {
        // Ask plugin system to perform anonymisation.
        $anonymiser->process($entity);

        // If the entity still exists after processing, then save anonymisation
        // timestamp on entity level. Current implementation requires that
        // developers add 'field_gdpr_anonymised' to the entity to support this
        // feature.
        $entity_reloaded = $this->entityTypeManager->getStorage($entity_type)->load($entity_id);
        if ($entity_reloaded && $entity_reloaded->hasField('field_gdpr_anonymised')) {
          $entity_reloaded->field_gdpr_anonymised = $this->time->getRequestTime();
          $entity_reloaded->save();
        }

        return TRUE;
      }
    }
    catch (\Throwable $e) {
      $placeholders = [
        '@type' => $entity_type,
        '@id' => $entity_id,
        '%message' => $e->getMessage(),
        '@backtrace' => $e->getTraceAsString(),
        '%time' => date('H:i:s'),
      ];
      try {
        $placeholders['link'] = $entity->toLink('View entity')->toString();
      }
      catch (\Exception $e) {
      }
      $this->logger->error('Anonymisaion of @type @id failed with error %message; @backtrace.', $placeholders);
    }
    return FALSE;
  }

}
