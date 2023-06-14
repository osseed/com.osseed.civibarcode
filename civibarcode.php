<?php

require_once 'civibarcode.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function civibarcode_civicrm_config(&$config) {
  _civibarcode_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 */
function civibarcode_civicrm_install() {
  return _civibarcode_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function civibarcode_civicrm_uninstall() {
  return _civibarcode_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function civibarcode_civicrm_enable() {
  return _civibarcode_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function civibarcode_civicrm_disable() {
  return _civibarcode_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function civibarcode_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _civibarcode_civix_civicrm_upgrade($op, $queue);
}

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add token services to the container.
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function civibarcode_civicrm_container(ContainerBuilder $container) {
  $container->addResource(new FileResource(__FILE__));
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.list', 'civibarcode_register_tokens']
  )->setPublic(TRUE);
  $container->findDefinition('dispatcher')->addMethodCall('addListener',
    ['civi.token.eval', 'civibarcode_evaluate_tokens']
  )->setPublic(TRUE);
}

/**
 * Registers the token for event registration barcode.
 *
 * @param \Civi\Token\Event\TokenRegisterEvent $e
 */
function civibarcode_register_tokens(\Civi\Token\Event\TokenRegisterEvent $e) {
  $e->entity('barcode')
    ->register('event_registration_barcode', ts('Event Registration Barcode'));
}

/**
 * Evaluates the token value for event registration barcode for each contact id.
 *
 * @param \Civi\Token\Event\TokenValueEvent $e
 */
function civibarcode_evaluate_tokens(\Civi\Token\Event\TokenValueEvent $e) {
  foreach ($e->getRows() as $row) {
    /** @var  \Civi\Token\TokenRow $row */

    $config = CRM_Core_Config::singleton();
    $upload_dir = $config->imageUploadDir;
    $image_path = $config->imageUploadURL;

    $participant_id = '';
    if (isset($row->context['contactId'])) {
      $cid = $row->context['contactId'];
      $query = "SELECT max(id) as pid FROM civicrm_participant WHERE contact_id = $cid";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $participant_id = $dao->pid;
      }

      if (!empty($participant_id)) {
        require_once __DIR__ . '/vendor/autoload.php';
        // Loading Font
        $font = new \BarcodeBakery\Common\BCGFontFile(__DIR__ . '/font/Arial.ttf', 18);
        // Don't forget to sanitize user inputs
        $filename = (string) strtotime('now') . "-" . $participant_id;
        // The arguments are R, G, and B for color
        $color_black = new \BarcodeBakery\Common\BCGColor(0, 0, 0);
        $color_white = new \BarcodeBakery\Common\BCGColor(255, 255, 255);

        try {
          // Set barcode config.
          $code = new \BarcodeBakery\Barcode\BCGcode128();
          $code->setScale(2); // Resolution
          $code->setThickness(30); // Thickness
          $code->setForegroundColor($color_black); // Color of bars
          $code->setBackgroundColor($color_white); // Color of spaces
          $code->setFont($font); // Font (or 0)
          $code->parse($filename); // Text

          // Save the barcode image to CiviCRM files directory
          $imgurl = $upload_dir . $filename . '.png';
          $drawing = new \BarcodeBakery\Common\BCGDrawing($code, $color_white);
          $drawing->finish(\BarcodeBakery\Common\BCGDrawing::IMG_FORMAT_PNG, $imgurl);
          $image_url = $image_path . $filename . '.png';
          $barcode = '<div><img src="' . $image_url . '" alt="Registration Barcode"></div>';
        } catch (\Throwable $th) {
          CRM_Core_Error::debug_log_message('Barcode image creation failed with error: ' . $th->getMessage());
          $barcode = '';
        }
      } else {
        $barcode = '';
      }
    }

    $row->format('text/html');
    $row->tokens('barcode', 'event_registration_barcode', $barcode);
  }
}
