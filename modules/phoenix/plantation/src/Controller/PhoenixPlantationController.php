<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides pages for the Phoenix Plantation module.
 */
final class PhoenixPlantationController extends ControllerBase {

  /**
   * Displays the Phoenix Plantation dashboard.
   *
   * @return array
   *   A Drupal render array.
   */
  public function dashboard(): array {
    return [
      '#type' => 'container',
      'heading' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Welcome to Phoenix Plantation'),
      ],
      'description' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t(
          'This is the first custom module developed for Phoenix OS.'
        ),
      ],
    ];
  }

}
