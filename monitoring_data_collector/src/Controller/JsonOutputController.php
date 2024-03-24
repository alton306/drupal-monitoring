<?php

namespace Drupal\monitoring_data_collector\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Access\AccessResult;

/**
 * Class JsonOutputController.
 */
class JsonOutputController extends ControllerBase {

  /**
   * Drupal\monitoring_data_collector\SiteDataService definition.
   *
   * @var \Drupal\monitoring_data_collector\SiteDataService
   */
  protected $siteDataService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->siteDataService = $container->get('monitoring_data_collector.site_data_collector');
    
    return $instance;
  }

  /**
   * Jsonoutput.
   *
   * @return string
   *   Return Siteinfo as a JsonOutputted string.
   */
  
  public function jsonOutput() {
    
    return new JsonResponse(
      $this->siteDataService->generateReport()
    );
  }

  public function access(){
    // $incomingIP = $_SERVER['REMOTE_ADDR'];
    // $restrictedIP = '88.211.108.202';

    // if ($incomingIP != $restrictedIP) {
    //   // Return 403 Access Denied page.  
    //   return AccessResult::forbidden();
    //  }
     return AccessResult::allowed();
  }

}
