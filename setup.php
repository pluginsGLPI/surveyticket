<?php

/*
   ------------------------------------------------------------------------
   Surveyticket
   Copyright (C) 2012-2013 by the Surveyticket plugin Development Team.

   https://forge.indepnet.net/projects/surveyticket
   ------------------------------------------------------------------------

   LICENSE

   This file is part of Surveyticket plugin project.

   Surveyticket plugin is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   Surveyticket plugin is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with Surveyticket plugin. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   Surveyticket plugin
   @author    David Durieux
   @copyright Copyright (c) 2012-2013 Surveyticket plugin team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/surveyticket
   @since     2012

   ------------------------------------------------------------------------
 */

define ("PLUGIN_SURVEYTICKET_VERSION","0.84+1.0");

// Init the hooks of surveyticket
function plugin_init_surveyticket() {
   global $PLUGIN_HOOKS,$CFG_GLPI;

   $PLUGIN_HOOKS['csrf_compliant']['surveyticket'] = true;
   
   if (isset($_SESSION["glpiID"])) {

      $PLUGIN_HOOKS['change_profile']['surveyticket'] = array('PluginSurveyticketProfile','changeprofile');
      PluginSurveyticketProfile::changeprofile();

      $plugin = new Plugin();
      if ($plugin->isActivated('surveyticket')) {

         Plugin::registerClass('PluginSurveyticketProfile',
              array('addtabon' => array('Profile')));

         if (PluginSurveyticketProfile::haveRight("config", 'r')) {
            $PLUGIN_HOOKS['menu_entry']['surveyticket'] = true;

            $PLUGIN_HOOKS['config_page']['surveyticket'] = 'front/menu.php';
         }         
         $PLUGIN_HOOKS['post_init']['surveyticket'] = 'plugin_surveyticket_post_init';

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
//         $PLUGIN_HOOKS['submenu_entry']['surveyticket']['options']['answers']['page']  = '/plugins/surveyticket/front/answer.php';

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
                'minGlpiVersion' => '0.84'
   );
}


// Optional : check prerequisites before install : may print errors or add to message after redirect
function plugin_surveyticket_check_prerequisites() {
   global $DB;

   if (!isset($_SESSION['glpi_plugins'])) {
      $_SESSION['glpi_plugins'] = array();
   }

   if (version_compare(GLPI_VERSION, '0.84', 'lt') || version_compare(GLPI_VERSION, '0.85', 'ge')) {
      echo __('Your GLPI version not compatible, require 0.84', 'surveyticket');
      return FALSE;
   }

   return TRUE;
}

function plugin_surveyticket_check_config() {
   return true;
}

function plugin_surveyticket_haveTypeRight($type,$right) {
   return true;
}

?>