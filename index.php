<?php


if(!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../..');
}

include (GLPI_ROOT."/inc/includes.php");

Html::redirect($CFG_GLPI['root_doc']."/plugins/surveyticket/front/menu.php");

?>