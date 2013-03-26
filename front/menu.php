<?php

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../../..');
}

include (GLPI_ROOT."/inc/includes.php");

Html::header("survey", $_SERVER["PHP_SELF"], "plugins", 
             "surveyticket", "menu");

echo "<table class='tab_cadre' width='250'>";
      
echo "<tr class='tab_bg_1'>";
echo "<th>";
echo "Menu";
echo "</th>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>";
echo "<a href='".$CFG_GLPI['root_doc']."/plugins/surveyticket/front/survey.php'>Questionnaires</a>";
echo "</td>";
echo "</tr>";

echo "<tr class='tab_bg_1'>";
echo "<td>";
echo "<a href='".$CFG_GLPI['root_doc']."/plugins/surveyticket/front/question.php'>Questions</a>";
echo "</td>";
echo "</tr>";

echo "</table>";

Html::footer();

?>