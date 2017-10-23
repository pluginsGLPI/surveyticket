<?php

/*
  ------------------------------------------------------------------------
  Surveyticket
  Copyright (C) 2012-2017 by the Surveyticket plugin Development Team.

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

 */


function update16to92() {
   global $DB;

   $migration = new Migration(92);

   $query = "CREATE TABLE `glpi_plugin_surveyticket_questions_tickets` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_surveyticket_questions_id` int(11) NOT NULL DEFAULT '0',
   `tickets_id` int(11) NOT NULL DEFAULT '0',
   `value` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;";

   $DB->queryOrDie($query);

   $migration->executeMigration();

   return true;
}