<?php


define ("PLUGIN_SURVEYTICKET_VERSION","1.0.0");

// Init the hooks of surveyticket
function plugin_init_surveyticket() {
   global $PLUGIN_HOOKS,$CFG_GLPI,$LANG;

   $PLUGIN_HOOKS['csrf_compliant']['surveyticket'] = true;
   
      if (isset($_SESSION["glpiID"])) {

         $plugin = new Plugin();
         if ($plugin->isActivated('surveyticket')) {
            if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
               if (isset($_GET['create_ticket'])) {
                  Html::redirect($CFG_GLPI['root_doc']."/plugins/surveyticket/front/displaysurvey.php");
                  exit;
               }
            }
            
            
            $PLUGIN_HOOKS['menu_entry']['surveyticket'] = true;
//            $PLUGIN_HOOKS['helpdesk_menu_entry']['surveyticket'] = true;
            
            $PLUGIN_HOOKS['config_page']['surveyticket'] = 'front/menu.php';
         }
         
         // Icons add, search...
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['add']['questions'] = 'front/question.form.php?add=1';
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['search']['questions'] = 'front/question.php';

         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['add']['answers'] = 'front/answer.form.php?add=1';
         
         
         // Fil ariane
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['questions']['title'] = "Questions";
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['questions']['page']  = '/plugins/surveyticket/front/question.php';

         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['answers']['title'] = "Answers";
         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['answers']['page']  = '/plugins/surveyticket/front/answer.php';

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

?>