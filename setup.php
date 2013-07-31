<?php


define ("PLUGIN_SURVEYTICKET_VERSION","1.0.0");

// Init the hooks of surveyticket
function plugin_init_surveyticket() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;

   $PLUGIN_HOOKS['csrf_compliant']['surveyticket'] = true;
   
      if (isset($_SESSION["glpiID"])) {

         $plugin = new Plugin();
         if ($plugin->isActivated('surveyticket')) {
//            if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
//               if (isset($_GET['create_ticket'])) {
//                  Html::redirect($CFG_GLPI['root_doc']."/plugins/surveyticket/front/displaysurvey.php");
//                  exit;
//               }
            if ((strpos($_SERVER['PHP_SELF'],"ticket.form.php") 
                        && !isset($_GET['id']))
                 || (strpos($_SERVER['PHP_SELF'],"helpdesk.public.php")
                        && isset($_GET['create_ticket']))
                 || (strpos($_SERVER['PHP_SELF'],"tracking.injector.php"))) {
               
//               register_shutdown_function('plugin_surveyticket_on_exit');
               $profile_User = new Profile_User();
               register_shutdown_function(array('Plugin', 'doOneHook'), 'surveyticket', 'on_exit');
               if (isset($_SESSION["helpdeskSaved"])) {
                  $_SESSION["plugin_surveyticket_helpdeskSaved"] = $_SESSION["helpdeskSaved"];
               }
               ob_start();
            }
            
            $PLUGIN_HOOKS['menu_entry']['surveyticket'] = true;
//            $PLUGIN_HOOKS['helpdesk_menu_entry']['surveyticket'] = true;
            
            $PLUGIN_HOOKS['config_page']['surveyticket'] = 'front/menu.php';
         }
         
         // Icons add, search...
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['add']['questions'] = 'front/question.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['search']['questions'] = 'front/question.php';
         
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['add']['survey'] = 'front/survey.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['search']['survey'] = 'front/survey.php';

         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['add']['answers'] = 'front/answer.form.php?add=1';
         
         
         // Fil ariane
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['questions']['title'] = "Questions";
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['questions']['page']  = '/plugins/surveyticket/front/question.php';

         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['answers']['title'] = "Answers";
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['answers']['page']  = '/plugins/surveyticket/front/answer.php';
         
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['survey']['title'] = "Surveys";
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['survey']['page']  = '/plugins/surveyticket/front/survey.php';

      }
}

// Name and Version of the plugin
function plugin_version_surveyticket() {
   return array('name'           => 'Survey ticket',
                'shortname'      => 'surveyticket',
                'version'        => PLUGIN_SURVEYTICKET_VERSION,
                'author'         =>'<a href="mailto:d.durieux@siprossii.com">David DURIEUX</a>',
                'homepage'       =>'',
                'minGlpiVersion' => '0.83'
   );
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_surveyticket_check_prerequisites() {
   global $LANG;
   if (GLPI_VERSION >= '0.83') {
      return true;
   } else {
      echo "error";
   }
}

function plugin_surveyticket_check_config() {
   return true;
}

function plugin_surveyticket_haveTypeRight($type,$right) {
   return true;
}


//function plugin_surveyticket_on_exit() {
//   global $DB;
//   
//   $DB->connect();
//   
//   $out = ob_get_contents();
//   ob_end_clean();
////echo $out;
//
//   $a_match = array();
//   preg_match("/select name='type' id='dropdown_type(?:\d+)' (?:.*)option value\='(\d)' selected /", $out, $a_match);
//   if (!isset($a_match[1])) {
//      echo $out;
//      return;
//   }
//   $type = $a_match[1];
//   
//   include_once 'inc/tickettemplate.class.php';
//   include_once 'inc/survey.class.php';
//   include_once 'inc/surveyquestion.class.php';
//   include_once 'inc/question.class.php';
//   include_once 'inc/answer.class.php';
//   
//   if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
//      PluginSurveyticketSurvey::getCentral($out);
//   } else {
//      PluginSurveyticketSurvey::getHelpdesk($out);
//   }   
//}

?>