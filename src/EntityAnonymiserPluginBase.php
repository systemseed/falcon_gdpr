<?php

namespace Drupal\falcon_gdpr;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for EntityAnonymiser plugins.
 */
abstract class EntityAnonymiserPluginBase extends PluginBase implements EntityAnonymiserPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Anonymiser service.
   *
   * @var \Drupal\falcon_gdpr\AnonymiserInterface
   */
  protected $anonymiser;

  /**
   * The config for Falcon GDPR module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database instance.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AnonymiserInterface $anonymiser, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, Connection $database, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->anonymiser = $anonymiser;
    $this->config = $config_factory->get('falcon_gdpr.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('falcon_gdpr.anonymiser'),
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('logger.channel.falcon_gdpr')
    );
  }

  /**
   * Checks if the plugin supports processing of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to anonymise.
   *
   * @return bool
   *   TRUE if the plugin can process the given entity.
   */
  public function isEntitySupported(EntityInterface $entity) {
    $definitions = $this->getPluginDefinition();
    if ($definitions['type'] === $entity->getEntityTypeId()) {
      return TRUE;
    }

    return FALSE;
  }

}
