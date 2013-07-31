<?php


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