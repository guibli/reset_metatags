<?php 
/**
 * @file
 * Contains \Drupal\reset_metatags\Form\ResetMetatagsForm.
 */

namespace Drupal\reset_metatags\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Configure custom settings for this site.
 */
class ResetMetatagsForm  extends FormBase {

/**
 * Returns a unique string identifying the form.
 *
 * @return string
 * The unique string identifying the form.
 */
public function getFormId() {
    return 'reset_metatags_admin_form';
}

/**
 * Form constructor.
 *
 * @param array $form
 * An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * The current state of the form.
 *
 * @return array
 * The form structure.
 */
public function buildForm(array $form, FormStateInterface $form_state) {

    $node_types = \Drupal\node\Entity\NodeType::loadMultiple();
    // If you need to display them in a drop down:
    $options = [];
    foreach ($node_types as $node_type) {
        $options[$node_type->id()] = $node_type->label();
    }
    
    // Logo settings for theme override.
    $form = array(
        'content_type' => array(
        '#type' => 'select', 
        '#title' => t('Content type list'),
        '#options' => $options,
        '#description' => t('Select content to reset'),
        ),
    );    
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#button_type' => 'primary',
      ];
      return $form;    
}
 
/**
 * Form submission handler.
 *
 * @param array $form
 * An associative array containing the structure of the form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 * The current state of the form.
 */
public function submitForm(array &$form, FormStateInterface $form_state) {    
    $meta = \Drupal::service('metatag.manager');
    $languages = \Drupal::languageManager()->getLanguages();    
    $content_type = $form_state->cleanValues()->getValue('content_type');
    //Load pattern per language
    foreach ($languages AS $langcode => $language) { 
        // Load the language_manager service
        $language_manager = \Drupal::service('language_manager');
        // Get the target language object
        $language = $language_manager->getLanguage($langcode);
        // Remember original language before this operation.
        $original_language = $language_manager->getConfigOverrideLanguage();
        // Set the translation target language on the configuration factory.
        $language_manager->setConfigOverrideLanguage($language);
        $config =  \Drupal::config('metatag.metatag_defaults.node__'.$content_type)->get();
        //Execute query if config is found
        if($config){
            \Drupal::database()->update('node__field_metatags')
            ->condition('bundle' , $content_type)
            ->condition('langcode',$langcode)
            ->fields([
                'field_metatags_value' => serialize($config['tags']),
            ])
            ->execute();
            drupal_set_message(t('Reset metatags for '.$content_type.' in '.$langcode));
        }else{
            drupal_set_message(t('Missing metatag config for '.$content_type), 'error');
        }

    }

    drupal_flush_all_caches();
    
}
}