<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Service;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides data for an individual Plantation Block Workspace.
 */
final class PlantationWorkspaceService {

  /**
   * Constructs the Plantation Workspace service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Builds workspace data for a Plantation Block.
   */
  public function getWorkspaceData(AssetInterface $block): array {
    $estateName = 'Not assigned';

    if (
      $block->hasField('parent') &&
      !$block->get('parent')->isEmpty()
    ) {
      $estate = $block->get('parent')->entity;

      if ($estate !== NULL) {
        $estateName = $estate->label();
      }
    }

    $phoenixCode = 'Not assigned';

    if (
      $block->hasField('field_phoenix_code') &&
      !$block->get('field_phoenix_code')->isEmpty()
    ) {
      $phoenixCode = $block->get('field_phoenix_code')->value;
    }

    $treeCount = '—';

    if (
      $block->hasField('field_tree_count') &&
      !$block->get('field_tree_count')->isEmpty()
    ) {
      $treeCount = number_format(
        (int) $block->get('field_tree_count')->value
      );
    }

    $recentActivities = [];

    $logStorage = $this->entityTypeManager->getStorage('log');

    $logIds = $logStorage->getQuery()
      ->condition('type', 'activity')
      ->condition('asset.target_id', $block->id())
      ->sort('timestamp', 'DESC')
      ->range(0, 5)
      ->accessCheck(TRUE)
      ->execute();

    $logs = $logStorage->loadMultiple($logIds);

    foreach ($logs as $log) {
      $recentActivities[] = [
        'id' => $log->id(),
        'name' => $log->label(),
        'timestamp' => (int) $log->get('timestamp')->value,
        'status' => $log->get('status')->value ?? '',
      ];
    }

    return [
      'block_name' => $block->label(),
      'phoenix_code' => $phoenixCode,
      'estate_name' => $estateName,
      'tree_count' => $treeCount,
      'area' => '—',
      'age' => '—',
      'health' => 'Not assessed',
      'recent_activities' => $recentActivities,
    ];
  }

}
