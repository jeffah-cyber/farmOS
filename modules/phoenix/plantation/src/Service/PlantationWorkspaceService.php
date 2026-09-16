<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Service;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
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
    private readonly DateFormatterInterface $dateFormatter,
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

    $treeCountValue = NULL;
    $treeCount = '—';

    if (
      $block->hasField('field_tree_count') &&
      !$block->get('field_tree_count')->isEmpty()
    ) {
      $treeCountValue = (int) $block->get('field_tree_count')->value;
      $treeCount = number_format($treeCountValue);
    }

    $areaValue = NULL;
    $area = '—';

    if (
      $block->hasField('field_block_area_hectares') &&
      !$block->get('field_block_area_hectares')->isEmpty()
    ) {
      $areaValue = (float) $block->get('field_block_area_hectares')->value;
      $area = number_format($areaValue, 2) . ' ha';
    }

    $plantingDate = 'Not assigned';
    $age = '—';

    if (
      $block->hasField('field_planting_date') &&
      !$block->get('field_planting_date')->isEmpty()
    ) {
      $plantingTimestamp = (int) $block->get('field_planting_date')->value;

      $plantingDate = $this->dateFormatter->format(
        $plantingTimestamp,
        'custom',
        'd M Y',
      );

      $planting = new \DateTimeImmutable('@' . $plantingTimestamp);
      $planting = $planting->setTimezone(new \DateTimeZone(date_default_timezone_get()));

      $today = new \DateTimeImmutable('today');
      $interval = $planting->diff($today);

      if ($interval->invert === 0) {
        $ageParts = [];

        if ($interval->y > 0) {
          $ageParts[] = $interval->y . ' ' .
            ($interval->y === 1 ? 'year' : 'years');
        }

        if ($interval->m > 0) {
          $ageParts[] = $interval->m . ' ' .
            ($interval->m === 1 ? 'month' : 'months');
        }

        if (empty($ageParts)) {
          $ageParts[] = $interval->d . ' ' .
            ($interval->d === 1 ? 'day' : 'days');
        }

        $age = implode(' ', $ageParts);
      }
    }

    $plantingDensity = '—';

    if (
      $treeCountValue !== NULL &&
      $areaValue !== NULL &&
      $areaValue > 0
    ) {
      $density = $treeCountValue / $areaValue;
      $plantingDensity = number_format($density, 0) . ' trees/ha';
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
      'area' => $area,
      'planting_date' => $plantingDate,
      'age' => $age,
      'planting_density' => $plantingDensity,
      'health' => 'Not assessed',
      'recent_activities' => $recentActivities,
    ];
  }

}
