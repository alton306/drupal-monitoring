<?php

namespace Drupal\monitoring_data_collector;

use FilesystemIterator;

/**
 * Class SiteDataCollector.
 */
class SiteDataCollector
{

    protected $siteUrl;
    protected $rootDirectory;
    protected $configPath;
    protected $installProfile;

    /**
     * Constructs a new SiteDataCollector object.
     */
    public function __construct()
    {
        global $base_url;
        $this->siteUrl = $base_url;
        $this->rootDirectory = DRUPAL_ROOT;
        $this->installProfile = \Drupal::installProfile();
        $this->configPath = $this->configPath();

    }
    /**
     * Checks to see if the site has a composer file
     */
    public function hasComposer()
    {
        return file_exists($this->configPath . 'composer.json');
    }
    /**
     * Checks to see if the site is in Git
     */
    public function hasGit()
    {
        return file_exists($this->configPath . '.git');
    }
    /**
     * Checks to see if the site uses Config Sync
     */
    public function hasConfigSync()
    {
        return (file_exists($this->configPath . 'config') && !$this->dir_is_empty($this->configPath . 'config') ? true : false);
    }

    /**
     * Checks to see if a directory is empty
     */
    public function dir_is_empty($dirname)
    {
        return !(new \FilesystemIterator($dirname))->valid();

    }

    /**
     * Creates the file path location for most of the config files
     */
    public function configPath()
    {

        $explodeRootPath = explode('/', $this->rootDirectory);
        array_pop($explodeRootPath);
        $configPath = "";
        foreach ($explodeRootPath as $partOfPath) {
            $configPath .= $partOfPath . '/';
        }

        return $configPath;

    }
    /**
     * gets the SSL info for the site
     */
    private function getSslInfo()
    {
      
        $originalParse = parse_url($this->siteUrl, PHP_URL_HOST);
        $get = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
        $read = stream_socket_client("ssl://" . $originalParse . ":443", $errno, $errstr,
            30, STREAM_CLIENT_CONNECT, $get);
        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

        $ssl = [
          'validFromTime' => str_replace(' ', 'T', date('Y-m-d h:i:s', $certinfo['validFrom_time_t'])),
          'validToTime' => str_replace(' ', 'T', date('Y-m-d h:i:s', $certinfo['validTo_time_t'])),
        ];

        return $ssl;
        
    }

    /**
     * Formats all the site info into an array
     */

    public function formateSiteInfo()
    {

        // $dbInfo = \Drupal\Core\Database\Database::getConnection()->getConnectionOptions();

        $siteData = [
            'SiteUrl' => $this->siteUrl,
            'siteSslInfo' => $this->getSslInfo(),
            'configInfo' => [
              'CoreVersion' => \Drupal::VERSION,
              'RootDir' => $this->rootDirectory,
              'Profile' => $this->$installProfile,
              'Php' => phpversion(),
              'Composer' => $this->hasComposer(),
              'Git' => $this->hasGit(),
              'ConfigSync' => $this->hasConfigSync(),
            ]
          ];

        return $siteData;
    }

    private function checkForUpdates()
    {

        $handler = \Drupal::moduleHandler();
        $handler->loadInclude('inc', 'update', 'update.report');
        $available = update_get_available(true);
        $modulesList = update_calculate_project_data($available);
        return $modulesList;

    }

    public function formateUpdatesReport()
    {
        $modules = $this->checkForUpdates();
        $modulesReport = [];

        foreach ($modules as $module) {
            $name = isset($module['title']) ? $module['title'] : null;
            $machineName = isset($module['name']) ? $module['name'] : null;
            $currentVersion = isset($module['existing_version']) ? $module['existing_version'] : null;
            $latestVersion = isset($module['latest_version']) ? $module['latest_version'] : null;
            $recommendedVersion = isset($module['recommended']) ? $module['recommended'] : null;
            $status = isset($module['status']) ? $module['status'] : null;

            //Creating an Array of needed Module Data
            $moduleDetails = [
                'name' => strval($name),
                'machineName' => strval($machineName),
                'currentVersion' => strval($currentVersion),
                'latestVersion' => strval($latestVersion),
                'recommendedVersion' => strval($recommendedVersion),
                'statusCode' => strval($status),
            ];

            array_push($modulesReport, $moduleDetails);
        }

        return $modulesReport;

    }

    public function generateReport(){
        $siteData = $this->formateSiteInfo();
        $siteData['modulesData'] = $this->formateUpdatesReport();

        return $siteData;
    }

}
