<?php

namespace Drupal\simple_decoupled_preview;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Preview log entity.
 *
 * @see \Drupal\simple_decoupled_preview\Entity\PreviewLogEntity.
 */
class PreviewLogEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\simple_decoupled_preview\Entity\PreviewLogEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view preview log entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit preview log entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete preview log entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add preview log entity entities');
  }

}
