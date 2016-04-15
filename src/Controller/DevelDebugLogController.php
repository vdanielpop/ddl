<?php

namespace Drupal\devel_debug_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DevelDebugLogController extends ControllerBase {

  /**
   * The Database Connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Serializer service.
   *
   * @var Symfony\Component\Serializer\Serializer;
   */
  protected $serializer;

  /**
   * The DateFormatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The FormBuilder object.
   *
   * @var Drupal\Core\Form\FormBuilderInterface;
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('serializer'),
      $container->get('form_builder')
    );
  }

  /**
   * DevelDebugLogController constructor.
   *
   * @param Connection $database
   * @param DateFormatter $dateFormatter
   * @param Serializer $serializer
   * @param FormBuilderInterface $formBuilder
   */
  public function __construct(Connection $database, DateFormatter $dateFormatter, Serializer $serializer, FormBuilderInterface $formBuilder) {
    $this->database = $database;
    $this->dateFormatter = $dateFormatter;
    $this->serializer = $serializer;
    $this->formBuilder = $formBuilder;
  }

  /**
   * Lists debug information.
   *
   * @return array
   *  Renderable array that contains a list of debug data.
   */
  public function listLogs() {
    $query = $this->database->select('devel_debug_log', 'm')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $results = $query->fields('m', ['timestamp', 'title', 'message', 'serialized'])
      ->orderBy('id', 'desc')
      ->execute();

    $rows = [];
    foreach ($results as $result) {
      if ($result->serialized) {
        $result->message = $this->serializer->unserialize($result->message);
      }

      $rows[] = array(
        'title' => $result->title,
        'time' => $this->dateFormatter
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
        '#delete_form' => $this->formBuilder->getForm('Drupal\devel_debug_log\Form\DevelDebugLogDeleteForm'),
      ),
      'pager' => array(
        '#type' => 'pager'
    ),
  );

    return $build;
  }

  /**
   * Provides debug output for later printing.
   *
   *  Usually, kint() outputs the debug information as the first thing after the
   * <body> tag. This function allows you to get that output and use it for
   * later printing.
   *
   * @param mixed $message
   *  The data that's displayed for debugging.
   *
   * @return string
   *  The debug information.
   */
  private function ob_kint($message) {
    ob_start();
    kint($message);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }
}