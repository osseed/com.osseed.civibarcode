<?php

require_once 'civibarcode.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function civibarcode_civicrm_config(&$config) {
  _civibarcode_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function civibarcode_civicrm_xmlMenu(&$files) {
  _civibarcode_civix_civicrm_xmlMenu($files);
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

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function civibarcode_civicrm_managed(&$entities) {
  return _civibarcode_civix_civicrm_managed($entities);
}


function civibarcode_civicrm_tokens( &$tokens ) {
    $tokens['event_registration_barcode'] = array( 'event_registration_barcode.bar' => 'Event Registration Barcode' );
}

function civibarcode_civicrm_tokenValues( &$values, &$contactIDs, $dontCare, $tokens, $context ) {
  if(array_key_exists("event_registration_barcode",$tokens) && isset($contactIDs['contact_id'])) {
    ini_set('memory_limit', '256M');
    $cid = $contactIDs['contact_id'];
    $query = "SELECT max(id) as id FROM civicrm_participant WHERE contact_id = ".$cid;
    $dao = CRM_Core_DAO::executeQuery( $query );
    $participant_id = '';
   while($dao->fetch()) {
     $participant_id = $dao->id;
   }
   if(!empty($participant_id)) {
    require_once 'barcodegen/class/BCGFontFile.php';
    require_once 'barcodegen/class/BCGColor.php';
    require_once 'barcodegen/class/BCGDrawing.php';
    require_once 'barcodegen/class/BCGcode39.barcode.php';
    $config = CRM_Core_Config::singleton();
    $png_upload_dir = $config->imageUploadDir;
    $image_path = $config->imageUploadURL;
    $code = strtotime("now")."-".$participant_id;
    // Loading Font
    $font = new BCGFontFile(dirname(__FILE__).DIRECTORY_SEPARATOR.'barcodegen/font/Arial.ttf', 18);

    // Don't forget to sanitize user inputs
    $filename = (string)$code;

    // The arguments are R, G, B for color.
    $color_black = new BCGColor(0, 0, 0);
    $color_white = new BCGColor(255, 255, 255);

    $drawException = null;
   try {
    $code = new BCGcode39();
    $code->setScale(2); // Resolution
    $code->setThickness(30); // Thickness
    $code->setForegroundColor($color_black); // Color of bars
    $code->setBackgroundColor($color_white); // Color of spaces
    $code->setFont($font); // Font (or 0)
    $code->parse($filename); // Text
   } catch(Exception $exception) {
     $drawException = $exception;
    }

    /* Here is the list of the arguments
    1 - Filename (empty : display on screen)
    2 - Background color */
    $barcode_image = $png_upload_dir.$filename.'.png';
    $drawing = new BCGDrawing($barcode_image, $color_white);
    if($drawException) {
      $drawing->drawException($drawException);
    } else {
      $drawing->setBarcode($code);
      $drawing->draw();
     }

   // Draw (or save) the image into PNG format.
    $drawing->finish(BCGDrawing::IMG_FORMAT_PNG);
    $imgurl = $image_path.$filename.'.png';

    $values[$cid]['event_registration_barcode.bar'] = '<div><img src="'.$imgurl.'" alt="Regiatration Barcode"></div>';
   } 
  }
}


