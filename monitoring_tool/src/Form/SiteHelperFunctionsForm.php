<?php

namespace Drupal\monitoring_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SiteHelperFunctionsForm.
 */
class SiteHelperFunctionsForm extends FormBase
{
    protected $collectDataService;

    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->collectDataService = $container->get('monitoring_tool.collect_data_service');

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'site_helper_functions_form';
    }

    /**
     * {@inheritdoc}
     */

    public function buildForm(array $form, FormStateInterface $form_state)
    {

        /**
         * TODO:
         *  This form will be used for site wide helper functions such as
         *      commiting config sync changes from the live site to master providing the site uses Git
         *      running permission fix on site
         *      renewing SSL Manually is its not added to the automatice check list
         *      update module list manually
         */

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['config_sync_changes'] = [
            '#type' => 'submit',
            '#value' => $this->t('Commit config sync changes'),
            "#weight" => 2,
            '#button_type' => 'primary',
            '#submit' => [[$this, 'sync_changes']],
            '#limit_validation_errors' => array(),
        ];

        $form['actions']['fix_permissions'] = [
            '#type' => 'submit',
            '#value' => $this->t('Fix site permissions'),
            "#weight" => 2,
            '#button_type' => 'primary',
            '#submit' => [[$this, 'fix_permissions']],
            '#limit_validation_errors' => array(),
        ];

        $form['actions']['renew_ssl'] = [
            '#type' => 'submit',
            '#value' => $this->t('Renew SSL'),
            "#weight" => 2,
            '#button_type' => 'primary',
            '#submit' => [[$this, 'renew_ssl']],
            '#validate' => [[$this, 'ssl_validate']],
            '#limit_validation_errors' => array(),
        ];

        $form['actions']['update_module_list'] = [
            '#type' => 'submit',
            '#value' => $this->t('Refresh Module List'),
            "#weight" => 2,
            '#button_type' => 'primary',
            '#submit' => [[$this, 'update_module_list']],
            '#limit_validation_errors' => array(),
        ];

        return $form;
    }

    public function sync_changes(array &$form, FormStateInterface $form_state)
    {
        $this->collectDataService->runSiteCommand($this->getSiteUrl(), 'config_sync');
    }

    public function fix_permissions(array &$form, FormStateInterface $form_state)
    {
        $this->collectDataService->runSiteCommand($this->getSiteUrl(), 'file_perms');
    }

    public function renew_ssl(array &$form, FormStateInterface $form_state)
    {   
        $this->collectDataService->runSiteCommand($this->getSiteUrl(), 'ssl_renew');
    }

    public function ssl_validate(array &$form, FormStateInterface $form_state)
    {
        //gets the DNS name server for the site
        $dnsNameServers = dns_get_record($this->getDomain($this->getSiteUrl()), DNS_NS);

        //checks to see if the site is using cloudflare which usually indicates that its using there SSL
        if ($dnsNameServers != null) {
            foreach ($dnsNameServers as $nameServer) {
                if ($nameServer['target']) {
                    if (str_contains($nameServer['target'], 'cloudflare.com')) {
                        $form_state->setErrorByName('renew_ssl', t('Site uses Cloudflare for SSL, Renewal cannot be run this way'));
                    }
                }

            }
        }else{
            $form_state->setErrorByName('renew_ssl', t('Unable to gather Domain infomation to see if site is running through Cloudflare. Please check the sites url is Valid'));
        }
        parent::validateForm($form, $form_state);
    }

    public function update_module_list(array &$form, FormStateInterface $form_state)
    {
        //manually updates the module list located on the site page
        $siteData = json_decode($this->collectDataService->getSiteData($this->getSiteUrl()), true);

        if ($this->collectDataService->UpdateSiteNode($siteData)) {
            \Drupal::messenger()->addMessage('Site data ppdated');
        } else {
            \Drupal::messenger()->addError('Unable to update site data at this time');
        }

    }

    public function getSiteUrl()
    {
        //gets the sites url from the node the form is currently on
        $route_name = \Drupal::routeMatch()->getRouteName();

        if ($route_name == 'entity.node.canonical') {
            $entity = \Drupal::routeMatch()->getParameter('node');
            if ($entity instanceof \Drupal\node\NodeInterface) {
                $nid = $entity->id();
            }
        }

        $node = Node::load($nid);
        return $node->title->value;

    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {

        parent::validateForm($form, $form_state);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
    }

    public function getDomain($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : '';
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }

}
