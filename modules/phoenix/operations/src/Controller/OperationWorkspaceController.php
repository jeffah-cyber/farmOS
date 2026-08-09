<?php

declare(strict_types=1);

namespace Drupal\phoenix_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\log\Entity\LogInterface;
use Drupal\phoenix_operations\Service\OperationWorkspaceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the Phoenix Operation Workspace.
 */
final class OperationWorkspaceController extends ControllerBase {

  /**
   * Constructs the controller.
   */
  public function __construct(
    private readonly OperationWorkspaceService $workspaceService,
  ) {}

  /**
   * Creates the controller from the service container.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('phoenix_operations.workspace'),
    );
  }

  /**
   * Returns the workspace title.
   */
  public function title(LogInterface $log): string {
    $this->validateOperation($log);

    return $log->label();
  }

  /**
   * Displays the Operation Workspace.
   */
  public function workspace(LogInterface $log): array {
    $this->validateOperation($log);

    $data = $this->workspaceService->getWorkspaceData($log);

    $editUrl = Url::fromRoute(
      'entity.log.edit_form',
      ['log' => $log->id()],
    )->toString();

    return [
      '#theme' => 'phoenix_operation_workspace',
      '#operation' => $data,
      '#edit_url' => $editUrl,
      '#attached' => [
        'library' => [
          'phoenix_core/ui',
        ],
      ],
      '#cache' => [
        'tags' => $log->getCacheTags(),
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Ensures the supplied log is an Activity operation.
   */
  private function validateOperation(LogInterface $log): void {
    if ($log->bundle() !== 'activity') {
      throw new NotFoundHttpException();
    }
  }

}
