<?php

namespace Drupal\simple_decoupled_preview;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\simple_decoupled_preview\Entity\PreviewLogEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of preview log entity entities.
 *
 * @ingroup simple_decoupled_preview
 */
class PreviewLogEntityListBuilder extends EntityListBuilder {

  /**
   * Drupal\Core\Datetime\DateFormatter definition.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this
      ->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query
        ->pager($this->limit);
    }
    return $query
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['uid'] = $this->t('User');
    $header['title'] = $this->t('Entity Title');
    $header['entity_uuid'] = $this->t('Entity UUID');
    $header['bundle'] = $this->t('Bundle');
    $header['langcode'] = $this->t('Language');
    $header['created'] = $this->t('Created');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    if (!($entity instanceof PreviewLogEntity)) {
      return parent::buildRow($entity);
    }

    $row['id'] = $entity->id();
    $row['uid'] = $entity->getOwnerName();
    $row['title'] = $entity->getTitle();
    $row['entity_uuid'] = $entity->get('entity_uuid')->value;
    $row['bundle'] = $entity->get('bundle')->value;
    $row['langcode'] = $entity->get('langcode')->value;
    $row['created'] = $this->dateFormatter->format($entity->getCreatedTime(), 'custom', 'm/d/y g:i A', date_default_timezone_get());
    return $row;
  }

}
