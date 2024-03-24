<?php

namespace Drupal\monitoring_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddSiteForm.
 */
class AddSiteForm extends FormBase
{
    protected $collectDataService;

    public static function create(ContainerInterface $container) {
        $instance = parent::create($container);
        $instance->collectDataService = $container->get('monitoring_tool.collect_data_service');
        
        return $instance;
      }
    

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'add_site_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $customerId = null)
    {

        /**
         * TODO:
         *  This form will allow users to add sites to the monitoring list
         *  it should check for the modules existence on the clients site
         *  if it dosnt exist dont allow for the site/module content types to be created
         *
         */

        $form['site_url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Site URL E.g. https://example.co.uk/'),
            '#required' => true,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();

        //checks to see if the field is empty
        if ($values['site_url'] == null) {

            $form_state->setErrorByName('site_url', $this->t('No URL found'));

        } else {

            //checks to see if a node with that url already exists
            if ($this->collectDataService->doesSiteExist($values['site_url'])) {
                $form_state->setErrorByName('description', $this->t('Site with that URL Found'));
            }

        }
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValues();
        $this->collectDataService->createSiteNode($values['site_url']);

        foreach ($form_state->getValues() as $key => $value) {
            \Drupal::messenger()->addMessage($key . ': ' . ($key === 'text_format' ? $value['value'] : $value));
        }
    }

}
