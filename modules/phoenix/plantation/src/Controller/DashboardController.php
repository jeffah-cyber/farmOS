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
      '#theme' => 'phoenix_dashboard',
      '#estate_count' => $data['estate_count'],
      '#block_count' => $data['block_count'],
      '#estate_rows' => $data['estate_rows'],
      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],
      '#cache' => [
        'tags' => ['asset_list'],
        'contexts' => ['user.permissions'],
      ],
    ];
  }

}
