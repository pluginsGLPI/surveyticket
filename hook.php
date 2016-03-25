<?php

/*
   ------------------------------------------------------------------------
   Surveyticket
   Copyright (C) 2012-2014 by the Surveyticket plugin Development Team.

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
   @copyright Copyright (c) 2012-2014 Surveyticket plugin team
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @link      https://forge.indepnet.net/projects/surveyticket
   @since     2012

   ------------------------------------------------------------------------
 */

function plugin_surveyticket_install() {
   global $DB;

   if (!TableExists('glpi_plugin_surveyticket_questions')) {
      $DB_file = GLPI_ROOT ."/plugins/surveyticket/install/mysql/plugin_surveyticket-empty.sql";
      $DBf_handle = fopen($DB_file, "rt");
      $sql_query = fread($DBf_handle, filesize($DB_file));
      fclose($DBf_handle);
      foreach ( explode(";\n", "$sql_query") as $sql_line) {
         if (Toolbox::get_magic_quotes_runtime()) $sql_line=Toolbox::stripslashes_deep($sql_line);
         if (!empty($sql_line)) $DB->query($sql_line);
      }

      include (GLPI_ROOT . "/plugins/surveyticket/inc/profile.class.php");
      $psProfile = new PluginSurveyticketProfile();
      $psProfile->initProfile();
   }
   if (!FieldExists("glpi_plugin_surveyticket_surveyquestions", "mandatory")) {
      include(GLPI_ROOT . "/plugins/surveyticket/install/update14_15.php");
      update14to15();
   }
   return true;
}



// Uninstall process for plugin : need to return true if succeeded
function plugin_surveyticket_uninstall() {
   global $DB;

   $query = "SHOW TABLES;";
   $result=$DB->query($query);
   while ($data=$DB->fetch_array($result)) {
      if (strstr($data[0], "glpi_plugin_surveyticket_")) {

         $query_delete = "DROP TABLE `".$data[0]."`;";
         $DB->query($query_delete) or die($DB->error());
      }
   }

   $query="DELETE FROM `glpi_displaypreferences`
           WHERE `itemtype` LIKE 'PluginSurveyticket%';";
   $DB->query($query) or die($DB->error());

   return true;
}



function plugin_surveyticket_post_init() {

   if ((strpos($_SERVER['PHP_SELF'], "front/ticket.form.php")
            && !isset($_GET['id'])
            && (!isset($_POST['id'])
               || $_POST['id'] == 0))
     || (strpos($_SERVER['PHP_SELF'], "front/helpdesk.public.php")
            && isset($_GET['create_ticket']))
     || (strpos($_SERVER['PHP_SELF'], "front/tracking.injector.php"))) {

     
      if (!isset($_POST['add'])) {
         if (strpos($_SERVER['PHP_SELF'], "ticket.form.php")) {
            Html::header(__('New ticket'), '', "maintain", "ticket");

            PluginSurveyticketSurvey::getCentral();
            Html::footer();
            exit;
         } else if (strpos($_SERVER['PHP_SELF'], "helpdesk.public.php")
                 || (strpos($_SERVER['PHP_SELF'], "tracking.injector.php"))) {

            Html::helpHeader(__('Simplified interface'), '', $_SESSION["glpiname"]);
            PluginSurveyticketSurvey::getHelpdesk();
            Html::helpFooter();
            exit;
         }
      
      }
      
   }
}

?>