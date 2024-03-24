<?php

namespace Drupal\monitoring_tool;

use GuzzleHttp\Client;

/**
 * Class CollectDataService.
 */
class CollectDataService
{

    /**
     * Constructs a new CollectDataService object.
     */
    public function __construct()
    {
    }

    /**
     * Gets the Data from the clients site
     */
    public function getSiteData($url)
    {

        $client = new Client([
            'base_url' => $url,
            'cookies' => true,
            'allow_redirects' => true,
            'debug' => false,
            'http_errors' => false,
        ]);

        $response = $client->post($url . '/monitoring_data_collector/jsonOutput', [
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() == 403) {
            \Drupal::messenger()->addMessage("Something has gone wrong, Site forbidden Access make sure the monitoring tool is being used from the beach server as the Monitoring data collector Controller is IP locked", 'error');
        } elseif ($response->getStatusCode() == 404) {
            \Drupal::messenger()->addMessage("Something has gone wrong, it could be that the requested site $url dosnt have the Monitoring data collector module installed please ensure its installed before continuing", 'error');
        } else {
            return $response->getBody()->getContents();
        }

        return 0;
    }

    public function runSiteCommand($url, $command)
    {
        $client = new Client([
            'base_url' => $url,
            'cookies' => true,
            'allow_redirects' => true,
            'debug' => false,
            'http_errors' => false,
        ]);
        $response = $client->post($url . "/monitoring_data_collector/RunHelperCommands/$command", [
            'headers' => [
                'Content-type' => 'application/json',
            ],
        ]);

        if ($response->getStatusCode() == 403) {
            \Drupal::messenger()->addMessage("Something has gone wrong, Site forbidden Access make sure the monitoring tool is being used from the beach server as the Monitoring data collector Controller is IP locked", 'error');
        } elseif ($response->getStatusCode() == 404) {
            \Drupal::messenger()->addMessage("Something has gone wrong, it could be that the requested site $url dosnt have the Monitoring data collector module installed please ensure its installed before continuing", 'error');
        } else {
            return $response->getBody()->getContents();
        }

        return 0;
    }
    /**
     * Sets the Status code for the module
     */
    public function setStatusCode($module)
    {
        switch ($module['statusCode']) {
            case 1:
                $status = "not_secure";
                break;
            case 2:
                $status = "revoked";
                break;
            case 3:
                $status = "not_supported";
                break;
            case 4:
                $status = "not_current";
                break;
            case 5:
                $status = "current";
                break;
            default:
                $status = "unknown";
        }

        return $status;
    }

    /**
     * Checks to see if the module exists and returns the modules node id
     */
    public function doesSiteModuleExist($moduleName, $siteId)
    {
        $siteEntity = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
            ->condition('type', 'site')
            ->condition('nid', $siteId)
            ->condition('field_site_modules.entity:node.field_machine_name', $moduleName)
            ->execute();

        if (!empty($siteEntity)) {
            $siteEntity = \Drupal::entityTypeManager()->getStorage('node')->load(reset($siteEntity));
            $modules = $siteEntity->get('field_site_modules');

            foreach ($modules->referencedEntities() as $module) {
                if ($module->get('field_machine_name')->value == $moduleName) {
                    return $module->id();
                }
            }
        }

        return null;
    }

    /**
     * Checks to see if the modules exist already for the site if they do they are updated,
     *  if they dont then they are created
     */

    public function createUpdateModuleNode($modulesData, $siteId)
    {

        $moduleIds = [];

        foreach ($modulesData as $module) {

            $status = $this->setStatusCode($module);
            $moduleId = $this->doesSiteModuleExist($module['machineName'], $siteId);

            if ($moduleId == null) {
                $moduleNode = \Drupal::entityTypeManager()->getStorage('node')->create([
                    'type' => 'module',
                    'title' => $module['name'],
                    'field_machine_name' => $module['machineName'],
                    'field_current_version' => $module['currentVersion'],
                    'field_latest_version' => $module['latestVersion'],
                    'field_recommended_version' => $module['recommendedVersion'],
                    'field_status' => $status,
                ]);
            } else {
                $moduleNode = \Drupal::entityTypeManager()->getStorage('node')->load($moduleId);
                $moduleNode->set('field_current_version', $module['currentVersion']);
                $moduleNode->set('field_latest_version', $module['latestVersion']);
                $moduleNode->set('field_recommended_version', $module['recommendedVersion']);
                $moduleNode->set('field_status', $status);
            }

            $moduleNode->save();
            $moduleIds[] = $moduleNode->id();
        }

        return $moduleIds;
    }

    /**
     * Gets a list of all the sites currently being monitored
     */
    public function getSites()
    {

        $clientWebSite = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'site',
        ]);

        return $clientWebSite;
    }

    /**
     * Checks to see if the site exists or not
     */
    public function doesSiteExist($siteUrl)
    {
        if (!$siteUrl) {
            \Drupal::logger('my_module')->error('siteUrl is missing');
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException();
        }

        $clientWebSite = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'site',
            'title' => $siteUrl,
        ]);

        $clientWebSite = reset($clientWebSite);

        return ($clientWebSite) ? true : false;
    }

    /**
     * creates a site node
     */

    public function createSiteNode($url)
    {
        $siteData = json_decode($this->getSiteData($url), true);

        $clientWebSite = \Drupal::entityTypeManager()->getStorage('node')->create([
            'type' => 'site',
            'title' => $url,
        ]);

        if (!empty($siteData) && $siteData !== 0) {

            $clientWebSite->set('field_uses_composer_', $siteData['configInfo']['Composer']);
            $clientWebSite->set('field_core_version', $siteData['configInfo']['CoreVersion']);
            $clientWebSite->set('field_uses_git', $siteData['configInfo']['Git']);
            $clientWebSite->set('field_php_version', $siteData['configInfo']['Php']);
            $clientWebSite->set('field_profile_name', $siteData['configInfo']['Profile']);
            $clientWebSite->set('field_root_directory_path', $siteData['configInfo']['RootDir']);
            $clientWebSite->set('field_ssl_info', ['value' => $siteData['siteSslInfo']['validFromTime'], 'end_value' => $siteData['siteSslInfo']['validToTime']]);
            $clientWebSite->set('field_uses_config_sync', $siteData['configInfo']['ConfigSync']);
            $clientWebSite->set('field_site_modules', $this->createUpdateModuleNode($siteData['modulesData'], $clientWebSite->id()));

        } else {

            \Drupal::messenger()->addMessage("Warning Could not connect to site to collect data at this time site node created but no data has been added will try again on nightly cron job", 'warning');
        }

        $clientWebSite->save();

    }
    /**
     * updates the site node
     */

    public function UpdateSiteNode($siteData)
    {
        $clientWebSite = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'site',
            'title' => $siteData['SiteUrl'],
        ]);

        $clientWebSite = reset($clientWebSite);

        if ($clientWebSite) {
            $clientWebSite->set('field_uses_composer_', $siteData['configInfo']['Composer']);
            $clientWebSite->set('field_core_version', $siteData['configInfo']['CoreVersion']);
            $clientWebSite->set('field_uses_git', $siteData['configInfo']['Git']);
            $clientWebSite->set('field_php_version', $siteData['configInfo']['Php']);
            $clientWebSite->set('field_profile_name', $siteData['configInfo']['Profile']);
            $clientWebSite->set('field_root_directory_path', $siteData['configInfo']['RootDir']);
            $clientWebSite->set('field_ssl_info', ['value' => $siteData['siteSslInfo']['validFromTime'], 'end_value' => $siteData['siteSslInfo']['validToTime']]);
            $clientWebSite->set('field_uses_config_sync', $siteData['configInfo']['ConfigSync']);
            $clientWebSite->set('field_site_modules', $this->createUpdateModuleNode($siteData['modulesData'], $clientWebSite->id()));
            $clientWebSite->save();

            return 1;
        }
        return 0;
    }

    /**
     * deletes all site and module data that has been collected on the site
     */
    public function deleteData($id)
    {

        $result = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'site',
            'nid' => $id,
        ]);

        if (!empty($result)) {

            $result = reset($result);
            $modules = $result->get('field_modules')->getValue();

            if (!empty($modules)) {
                foreach ($modules as $module) {
                    $entity = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
                        'type' => 'module',
                        'id' => $module['target_id'],
                    ]);
                    if (empty($entity)) {
                        continue;
                    }
                    $entity = reset($entity);
                    $entity->delete();
                }
            }

            $result->delete();
        }
    }

    /**
     * removes old modules that are no longer installed
     */
    public function moduleCleanup($moduleData)
    {
    }
}
