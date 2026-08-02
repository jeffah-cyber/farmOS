<?php

declare(strict_types=1);

namespace Drupal\phoenix_plantation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\phoenix_plantation\Service\DashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the Phoenix Plantation dashboard.
 */
final class DashboardController extends ControllerBase {

  /**
   * Constructs the dashboard controller.
   */
  public function __construct(
    private readonly DashboardService $dashboardService,
  ) {}

  /**
   * Creates the controller from Drupal's service container.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_plantation.dashboard'),
    );
  }

  /**
   * Displays the Phoenix Plantation dashboard.
   */
  public function dashboard(): array {
    $data = $this->dashboardService->getDashboardData();

    return [
      'intro' => [
        '#markup' => '<p>Live operational summary for Phoenix estates.</p>',
      ],

      'summary' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['phoenix-dashboard-summary'],
        ],
        'estates' => [
          '#markup' => '<h2>Total Estates: ' .
            $data['estate_count'] .
            '</h2>',
        ],
        'blocks' => [
          '#markup' => '<h2>Plantation Blocks: ' .
            $data['block_count'] .
            '</h2>',
        ],
      ],

      'estate_heading' => [
        '#markup' => '<h2>Estate Portfolio</h2>',
      ],

      'estate_table' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Estate'),
          $this->t('Phoenix Code'),
          $this->t('Lifecycle'),
        ],
        '#rows' => $data['estate_rows'],
        '#empty' => $this->t('No estates have been created yet.'),
      ],

      '#cache' => [
        'tags' => ['asset_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
