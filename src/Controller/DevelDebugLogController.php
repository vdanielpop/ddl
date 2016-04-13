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
   * DevelDebugLogController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public function listLogs() {
    $query = $this->database->select('devel_debug_log', 'm')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $results = $query->fields('m', ['timestamp', 'title', 'message', 'serialized'])
      ->orderBy('id', 'desc')
      ->execute();

    $rows = [];
    foreach ($results as $result) {
      if ($result->serialized) {
        $result->message = unserialize($result->message);
      }

      $rows[] = array(
        'title' => $result->title,
        'time' => \Drupal::service('date.formatter')
            ->format($result->timestamp, 'short'),
        'message' => $this->ob_kint($result->message),
      );
    }

    if (empty($rows)) {
      return array(
        '#markup' => t('No debug messages.'),
      );
    }

    $build = array(
      'messages' => array(
        '#theme' => 'devel_debug_log_list',
        '#content' => $rows,
        '#delete_form' => \Drupal::formBuilder()->getForm('Drupal\devel_debug_log\Form\DevelDebugLogDeleteForm'),
      ),
      'pager' => array(
        '#type' => 'pager'
    ),
  );

    return $build;
  }

  private function ob_kint($message) {
    ob_start();
    kint($message);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }
}