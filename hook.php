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


?>