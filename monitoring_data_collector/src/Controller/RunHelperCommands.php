<?php

namespace Drupal\monitoring_data_collector\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class JsonOutputController.
 */
class RunHelperCommands extends ControllerBase
{

    /**
     * Drupal\monitoring_data_collector\SiteDataService definition.
     *
     * @var \Drupal\monitoring_data_collector\SiteDataService
     */
    protected $siteDataService;
    protected $commandPath;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);

        $module_handler = \Drupal::service('module_handler');
        $module_path = '/' . $module_handler->getModule('monitoring_data_collector')->getPath();

        $instance->siteDataService = $container->get('monitoring_data_collector.site_data_collector');
        $instance->commandPath = 'python3 ' . DRUPAL_ROOT . $module_path . '/scripts/';

        return $instance;
    }

    /**
     * Jsonoutput.
     *
     * @return string
     *   Return Siteinfo as a JsonOutputted string.
     */

    public function runCommand($command)
    {
        $response = $this->run_command($command);

        return new JsonResponse(
            $response
        );
    }

    public function access()
    {
        // $incomingIP = $_SERVER['REMOTE_ADDR'];
        // $restrictedIP = '88.211.108.202';

        // if ($incomingIP != $restrictedIP) {
        //   // Return 403 Access Denied page.
        //   return AccessResult::forbidden();
        //  }
        return AccessResult::allowed();
    }

    private function run_command($command)
    {
        //creates a command that can be run in terminal
        $rootDriectory = DRUPAL_ROOT;

        $command = escapeshellcmd($this->commandPath . "$command.py '$rootDriectory'" );
        //excutes command
        $output = shell_exec($command);

        $response = [
            'response' => $output,
        ];

        return $response;

    }

}
