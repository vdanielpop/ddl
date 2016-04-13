<?php

namespace Drupal\devel_debug_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DevelDebugLogController extends ControllerBase {

  /**
   * The Database Connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * TableSortExampleController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function listLogs() {
    // TODO Add pagination.
    $results = $this->database->select('devel_debug_log', 'm')
      ->fields('m', ['timestamp', 'title', 'message', 'serialized'])
      ->orderBy('id', 'desc')
      ->execute();

    $rows = [];
    foreach ($results as $result) {
      if ($result->serialized) {
        $result->message = unserialize($result->message);
      }

      // TODO Find a way to catch the output of kint() and print it inside the table, maybe ob_start() and others.
      kint($result->message);
      $rows[] = [
        'title' => $result->title,
        'time' => \Drupal::service('date.formatter')
            ->format($result->timestamp, 'short'),
        'message' => '',
      ];
    }

    if (empty($rows)) {
      return [
        '#markup' => t('No messages'),
      ];
    }

    return [
      '#theme' => 'table',
      '#rows' => $rows,
    ];
  }
}