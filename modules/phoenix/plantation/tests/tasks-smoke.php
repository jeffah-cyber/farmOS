<?php

/**
 * @file
 * Integration smoke test for an installed Phoenix development site.
 *
 * Run with drush php:script path/to/tasks-smoke.php. All fixtures are rolled
 * back, including on failure. Uses the actual task form, storage and renderer.
 */

declare(strict_types=1);

use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Url;
use Drupal\phoenix_plantation\Controller\PlantationWorkspaceController;
use Drupal\phoenix_plantation\Form\PlantationTaskForm;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
  if (!$condition) {
    throw new \RuntimeException($message);
  }
  $checks++;
  print "PASS: $message\n";
};
$manager = \Drupal::entityTypeManager();
$switcher = \Drupal::service('account_switcher');
$switcher->switchTo($manager->getStorage('user')->load(1));
$transaction = \Drupal::database()->startTransaction();
try {
  $assets = $manager->getStorage('asset');
  $blocks = [];
  foreach (['A', 'B'] as $suffix) {
    $block = $assets->create([
      'type' => 'land',
      'name' => 'Tasks smoke Block ' . $suffix,
      'land_type' => 'plantation_block',
      'status' => 'active',
    ]);
    $block->save();
    $blocks[] = $block;
  }
  $service = \Drupal::service('phoenix_plantation.workspace');
  $check($service->getUpcomingTasks($blocks[0]) === [], 'Empty Block has no scheduled tasks');
  $check(Url::fromRoute('phoenix_plantation.task_add', ['asset' => $blocks[0]->id()])->access(), 'Administrator can access Add Task');

  $state = (new FormState())->setValues([
    'name' => '  Inspect <script>Block A</script>  ',
    'due_date' => '2026-09-22',
  ]);
  \Drupal::formBuilder()->submitForm(PlantationTaskForm::class, $state, $blocks[0]);
  $check(!$state->hasAnyErrors(), 'Task form validates and submits: ' . implode('; ', $state->getErrors()));
  $task = $state->get('task');
  $check($task && !$task->isNew(), 'Task is a persisted activity log');
  $check($task->bundle() === 'activity' && $task->get('status')->value === 'pending', 'Uses native activity type and Pending state');
  $check((int) $task->get('asset')->target_id === (int) $blocks[0]->id(), 'Task belongs to the selected Plantation Block');
  $check(date('Y-m-d', (int) $task->get('timestamp')->value) === '2026-09-22', 'Due date round-trips through native timestamp');
  $check(count($service->getUpcomingTasks($blocks[0])) === 1, 'Saved task appears under Upcoming Tasks');
  $check($service->getUpcomingTasks($blocks[1]) === [], 'Task does not leak to another Block');
  $state->setProgrammed(FALSE);
  $check($state->getRedirect()->getRouteName() === 'phoenix_plantation.workspace', 'Save returns to Block workspace');

  $earlier = $manager->getStorage('log')->create([
    'type' => 'activity',
    'name' => 'Overdue task',
    'asset' => [$blocks[0]->id()],
    'timestamp' => strtotime('2026-01-01 12:00:00'),
    'status' => 'pending',
  ]);
  $earlier->save();
  $tasks = $service->getUpcomingTasks($blocks[0]);
  $check(count($tasks) === 2 && $tasks[0]['name'] === 'Overdue task', 'Overdue tasks remain visible and tasks sort earliest first');

  $controller = PlantationWorkspaceController::create(\Drupal::getContainer());
  $build = $controller->workspace($blocks[0]);
  $html = (string) \Drupal::service('renderer')->renderInIsolation($build);
  $check(str_contains($html, 'Upcoming Tasks') && str_contains($html, '22 Sep 2026') && str_contains($html, 'Pending'), 'Workspace renders task name, due date and status');
  $check(!str_contains($html, '<script>Block A</script>') && str_contains($html, '&lt;script&gt;Block A&lt;/script&gt;'), 'Task names are HTML escaped');
  $check(str_contains($html, 'Recent Operations') && str_contains($html, 'Block Information'), 'Existing workspace sections still render');

  $task->set('status', 'done')->save();
  $check(count($service->getUpcomingTasks($blocks[0])) === 1, 'Done tasks leave Upcoming Tasks');
  $earlier->set('status', 'abandoned')->save();
  $check($service->getUpcomingTasks($blocks[0]) === [], 'Abandoned tasks leave Upcoming Tasks');
  $task->set('status', 'pending')->save();
  $check(count($service->getUpcomingTasks($blocks[0])) === 1, 'Reopened tasks return to Upcoming Tasks');

  foreach ([['name' => ' ', 'due_date' => '2026-09-22'], ['name' => 'Invalid date', 'due_date' => '2026-02-30'], ['name' => 'Missing date', 'due_date' => '']] as $values) {
    $invalid = (new FormState())->setValues($values);
    \Drupal::formBuilder()->submitForm(PlantationTaskForm::class, $invalid, $blocks[0]);
    $check($invalid->hasAnyErrors(), 'Invalid task input is rejected: ' . json_encode($values));
    $invalid->clearErrors();
  }

  $estate = $assets->create(['type' => 'land', 'name' => 'Smoke estate', 'land_type' => 'estate']);
  $estate->save();
  $rejected = FALSE;
  try {
    $form = PlantationTaskForm::create(\Drupal::getContainer());
    $form->buildForm([], new FormState(), $estate);
  }
  catch (NotFoundHttpException) {
    $rejected = TRUE;
  }
  $check($rejected, 'Non-Block assets cannot use the task form');
  $switcher->switchTo(new AnonymousUserSession());
  try {
    $manager->getAccessControlHandler('log')->resetCache();
    $manager->getAccessControlHandler('asset')->resetCache();
    $check(!Url::fromRoute('phoenix_plantation.task_add', ['asset' => $blocks[0]->id()])->access(), 'Anonymous users cannot create tasks');
    $check($service->getUpcomingTasks($blocks[0]) === [], 'Users without log access cannot read tasks');
  }
  finally {
    $switcher->switchBack();
  }
  print "Completed $checks integration checks.\n";
}
finally {
  \Drupal::messenger()->deleteAll();
  $transaction->rollBack();
  $switcher->switchBack();
  $manager->getStorage('log')->resetCache();
  $manager->getStorage('asset')->resetCache();
  print "All smoke-test records rolled back.\n";
}
