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

function plugin_surveyticket_install() {
   global $DB, $LANG;

   $DB_file = GLPI_ROOT ."/plugins/surveyticket/install/mysql/plugin_surveyticket-"
              .PLUGIN_SURVEYTICKET_VERSION."-empty.sql";
   $DBf_handle = fopen($DB_file, "rt");
   $sql_query = fread($DBf_handle, filesize($DB_file));
   fclose($DBf_handle);
   foreach ( explode(";\n", "$sql_query") as $sql_line) {
      if (Toolbox::get_magic_quotes_runtime()) $sql_line=Toolbox::stripslashes_deep($sql_line);
      if (!empty($sql_line)) $DB->query($sql_line);
   }
   
   return true;
}

// Uninstall process for plugin : need to return true if succeeded
function plugin_surveyticket_uninstall() {
   global $DB;

   $query = "SHOW TABLES;";
   $result=$DB->query($query);
   while ($data=$DB->fetch_array($result)) {
      if (strstr($data[0],"glpi_plugin_surveyticket_")) {

         $query_delete = "DROP TABLE `".$data[0]."`;";
         $DB->query($query_delete) or die($DB->error());
      }
   }

   $query="DELETE FROM `glpi_displaypreferences`
           WHERE `itemtype` LIKE 'PluginSurveyticket%';";
   $DB->query($query) or die($DB->error());

   return true;
}


function plugin_surveyticket_on_exit() {
   global $DB;

   $DB->connect();
   
   $out = ob_get_contents();
   ob_end_clean();
//echo $out;

   $a_match = array();
   preg_match("/select name='type' id='dropdown_type(?:\d+)' (?:.*)option value\='(\d)' selected /", $out, $a_match);
   if (!isset($a_match[1])) {
      echo $out;
      return;
   }
   $type = $a_match[1];

   include_once 'inc/tickettemplate.class.php';
   include_once 'inc/survey.class.php';
   include_once 'inc/surveyquestion.class.php';
   include_once 'inc/question.class.php';
   include_once 'inc/answer.class.php';
   
   if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
      PluginSurveyticketSurvey::getCentral($out);
   } else {
      PluginSurveyticketSurvey::getHelpdesk($out);
   }   
}

?>