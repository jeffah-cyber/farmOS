<?php

declare(strict_types=1);

namespace Drupal\phoenix_estate\Service;

use Drupal\asset\Entity\AssetInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides data for an individual Estate Workspace.
 */
final class EstateWorkspaceService {

  /**
   * Constructs the Estate Workspace service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly DateFormatterInterface $dateFormatter,
  ) {}

  /**
   * Builds workspace data for an Estate.
   */
  public function getWorkspaceData(AssetInterface $estate): array {
    $assetStorage = $this->entityTypeManager->getStorage('asset');

    $blockIds = $assetStorage->getQuery()
      ->condition('type', 'land')
      ->condition('land_type', 'plantation_block')
      ->condition('parent.target_id', $estate->id())
      ->sort('name', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $blocks = $assetStorage->loadMultiple($blockIds);

    $blockRows = [];

    foreach ($blocks as $block) {
      $blockCode = 'Not assigned';

      if (
        $block->hasField('field_phoenix_code') &&
        !$block->get('field_phoenix_code')->isEmpty()
      ) {
        $blockCode = $block->get('field_phoenix_code')->value;
      }

      $blockRows[] = [
        'id' => $block->id(),
        'name' => $block->label(),
        'code' => $blockCode,
      ];
    }

    $phoenixCode = 'Not assigned';

    if (
      $estate->hasField('field_phoenix_code') &&
      !$estate->get('field_phoenix_code')->isEmpty()
    ) {
      $phoenixCode = $estate->get('field_phoenix_code')->value;
    }

    $lifecycle = 'Not assigned';

    if (
      $estate->hasField('field_estate_lifecycle') &&
      !$estate->get('field_estate_lifecycle')->isEmpty()
    ) {
      $term = $estate->get('field_estate_lifecycle')->entity;

      if ($term !== NULL) {
        $lifecycle = $term->label();
      }
    }

    $areaHectares = 'Not assigned';

    if (
      $estate->hasField('field_estate_area_hectares') &&
      !$estate->get('field_estate_area_hectares')->isEmpty()
    ) {
      $value = $estate->get('field_estate_area_hectares')->value;
      $areaHectares = number_format((float) $value, 2) . ' ha';
    }

    $tenure = 'Not assigned';

    if (
      $estate->hasField('field_estate_tenure') &&
      !$estate->get('field_estate_tenure')->isEmpty()
    ) {
      $item = $estate->get('field_estate_tenure')->first();

      if ($item !== NULL) {
        $allowedValues = $item->getFieldDefinition()
          ->getFieldStorageDefinition()
          ->getSetting('allowed_values');

        $value = $item->value;

        if (isset($allowedValues[$value])) {
          $tenure = $allowedValues[$value];
        }
        else {
          $tenure = $value;
        }
      }
    }

    $acquisitionDate = 'Not assigned';

    if (
      $estate->hasField('field_estate_acquisition_date') &&
      !$estate->get('field_estate_acquisition_date')->isEmpty()
    ) {
      $timestamp = (int) $estate->get('field_estate_acquisition_date')->value;

      $acquisitionDate = $this->dateFormatter->format(
        $timestamp,
        'custom',
        'd M Y',
      );
    }

    return [
      'estate_name' => $estate->label(),
      'phoenix_code' => $phoenixCode,
      'lifecycle' => $lifecycle,
      'area_hectares' => $areaHectares,
      'tenure' => $tenure,
      'acquisition_date' => $acquisitionDate,
      'edit_url' => $estate->toUrl('edit-form')->toString(),
      'block_count' => count($blockRows),
      'block_rows' => $blockRows,
    ];
  }

}
