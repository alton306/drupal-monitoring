<?php

namespace Drupal\monitoring_tool\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TestController.
 */
class TestController extends ControllerBase
{

  /**
   * Drupal\monitoring_tool\CollectDataService definition.
   *
   * @var \Drupal\monitoring_tool\CollectDataService
   */
  protected $collectDataService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    $instance = parent::create($container);
    $instance->collectDataService = $container->get('monitoring_tool.collect_data_service');
    $instance->SiteData = $container->get('monitoring_data_collector.site_data_collector');
    return $instance;
  }

  /**
   * Test.
   *
   * @return string
   *   Return Hello string.
   */
  public function test()
  {
    $inputData = $this->collectDataService->getSiteData('https://fluid-reporting.ralton.beach.fluid-staging.co.uk');
    // kint($inputData);
    //$inputData = json_encode($this->SiteData->generateReport());
    $data = json_decode($inputData, true);

    $clientWebSite = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'type' => 'site',
      'title' => 'https://fluid-reporting.ralton.beach.fluid-staging.co.uk',
    ]);

    $clientWebSite = reset($clientWebSite);
    kint($clientWebSite);
    kint($data);

    die;

    // $moduleEntity = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    //   ->condition('type', 'site')
    //   ->condition('nid', 1)
    //   ->condition('field_site_modules.entity:node.field_machine_name', 'drupal')
    //   ->execute();

    // kint($moduleEntity);
    // die;

    //$moduleEntity = \Drupal::entityTypeManager()->getStorage('node')->load(reset($moduleEntity));

    // $modules = $moduleEntity->get('field_site_modules');

    // foreach ($modules->referencedEntities() as $module) {
    //   if($module->get('field_machine_name')->value == 'drupal'){
    //     kint($moduleNode = \Drupal::entityTypeManager()->getStorage('node')->load($module->id()));
    //     $moduleNode->set('field_current_version', '9.1');
    //     $moduleNode->save();
    //   }
    // }

    // kint($module->get('field_machine_name')->getValue()[0]['value']);

    // $siteExists = $this->collectDataService->doesSiteExist($data['SiteUrl']);
    // if (!$siteExists) {
    // }
    // kint($data);

    return [
      '#type' => 'markup',
      '#markup' => $this->t('made it')
    ];
  }
}
