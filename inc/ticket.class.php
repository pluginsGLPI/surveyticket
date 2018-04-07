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

  ------------------------------------------------------------------------

  @package   Surveyticket plugin
  @author    David Durieux
  @author    Infotel
  @copyright Copyright (c) 2012-2017 Surveyticket plugin team
  @license   AGPL License 3.0 or (at your option) any later version
  http://www.gnu.org/licenses/agpl-3.0-standalone.html
  @link      https://github.com/pluginsGLPI/surveyticket
  @since     2012

  ------------------------------------------------------------------------
 */


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginSurveyticketTicket
 */
class PluginSurveyticketTicket extends CommonDBTM {

   static $rightname = "ticket";

   /**
    * Get name of this type
    *
    * @param int $nb
    * @return text name of this type by language of the user connected
    *
    */
   static function getTypeName($nb = 0) {
      return _n('Ticket', 'Tickets', $nb);
   }

   /**
    * @param Ticket $ticket
    */
   static function emptyTicket(Ticket $ticket) {
      if (!empty($_POST)) {
         self::setSessions($_POST);
      }if (!empty($_REQUEST) && !empty($_REQUEST['_tickettemplates_id'])) {
         $ticket = new Ticket();
         $ticketTemplate = $ticket->getTicketTemplateToUse(false, $_REQUEST['type'], $_REQUEST['itilcategories_id'], $_REQUEST['entities_id']);
         if ($_REQUEST['_tickettemplates_id'] == $ticketTemplate->fields['id']) {
            self::setSessions($_REQUEST);
         }
      }
   }

   /**
    * @param $input
    * @param bool $add
    */
   static function setSessions($input, $add = false) {
      foreach ($input as $question => $answer) {
         if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
            $psAnswer = new PluginSurveyticketAnswer();
            $psQuestion = new PluginSurveyticketQuestion();
            $qid = str_replace("question", "", $question);
            $psQuestion->getFromDB($qid);

            if (is_array($answer)) {
               foreach ($answer as $val) {
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($val)) {
                     if (isset($input["text-" . $qid . "-" . $val])
                        AND $input["text-" . $qid . "-" . $val] != '') {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $input["text-" . $qid . "-" . $val];
                     } else {
                        $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = str_replace('\r', "", $val);
                     }
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$val] = $val;
                  }
               }
            } else {
               if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && $psAnswer->getFromDB($answer)) {
                  if (isset($input["text-" . $qid . "-" . $answer])
                     AND $input["text-" . $qid . "-" . $answer] != '') {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = $input["text-" . $qid . "-" . $answer];
                  } else {
                     $_SESSION['glpi_plugin_surveyticket_ticket'][$qid][$answer] = str_replace('\r', "", $answer);
                  }
               } else {
                  $_SESSION['glpi_plugin_surveyticket_ticket'][$qid] = $answer;
               }
            }
         }
      }
      $_SESSION['glpi_plugin_surveyticket_ticket']['add'] = false;
      if ($add == true) {
         $_SESSION['glpi_plugin_surveyticket_ticket']['add'] = true;
      }
   }

   /**
    * Returns true if using the template
    *
    * @param $type
    * @param $itilcategories_id
    * @param $entities_id
    * @param $interface
    * @return bool
    */
   static function isSurveyTicket($type, $itilcategories_id, $entities_id, $interface) {
      // Load ticket template if available :
      $ticket                         = new Ticket();
      $ticketTemplate                 = $ticket->getTicketTemplateToUse(false, $type, $itilcategories_id, $entities_id);
      $psTicketTemplate               = new PluginSurveyticketTicketTemplate();

      if ($interface == 'central') {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_central`='1'"));
      } else {
         $a_tickettemplates = current($psTicketTemplate->find("`tickettemplates_id`='" . $ticketTemplate->fields['id'] . "'
                                              AND `type`='" . $type . "'
                                              AND `is_helpdesk`='1'"));
      }

      //if template exists
      if (isset($a_tickettemplates['plugin_surveyticket_surveys_id'])) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Return the questionnaire
    *
    * @param type $type
    * @param type $itilcategories_id
    * @param type $entities_id
    * @param $interface
    * @return array
    */
   static function getSurveyTicket($type, $itilcategories_id, $entities_id, $interface) {
      global $DB;

      // If values are saved in session we retrieve it
      if (isset($_SESSION['glpi_plugin_surveyticket_ticket'])) {
         $session = $_SESSION['glpi_plugin_surveyticket_ticket'];
         unset($_SESSION['glpi_plugin_surveyticket_ticket']);
      } else {
         $session = array();
      }

      // Load ticket template if available :
      $ticket = new Ticket();
      $ticketTemplate = $ticket->getTicketTemplateToUse(false, $type, $itilcategories_id, $entities_id);
      $psTicketTemplate = new PluginSurveyticketTicketTemplate();
      $plugin_surveyticket_surveys_id = 0;
      if ($interface == 'central') {
         $sql = "SELECT * FROM `glpi_plugin_surveyticket_tickettemplates`"
                 . " LEFT JOIN `glpi_plugin_surveyticket_surveys` "
                 . "    ON `plugin_surveyticket_surveys_id` = `glpi_plugin_surveyticket_surveys`.`id`"
                 . " WHERE `tickettemplates_id`='" . $ticketTemplate->fields['id'] . "' "
                 . "    AND `type`='" . $type . "'"
                 . "    AND `is_central`='1'";
         $result = $DB->query($sql);
         $list = [];
         if ($result) {
            while ($data = $DB->fetch_array($result)) {
               $list[$data['entities_id']] = $data['plugin_surveyticket_surveys_id'];
            }
         }
         if (isset($list[$entities_id])) {
            $plugin_surveyticket_surveys_id = $list[$entities_id];
         } else {
            foreach (getAncestorsOf('glpi_entities', $entities_id) as $ent_id) {
               if (isset($list[$ent_id])) {
                  $plugin_surveyticket_surveys_id = $list[$ent_id];
                  break;
               }
            }
         }
      } else {
         $sql = "SELECT * FROM `glpi_plugin_surveyticket_tickettemplates`"
                 . " LEFT JOIN `glpi_plugin_surveyticket_surveys` "
                 . "    ON `plugin_surveyticket_surveys_id` = `glpi_plugin_surveyticket_surveys`.`id`"
                 . " WHERE `tickettemplates_id`='" . $ticketTemplate->fields['id'] . "' "
                 . "    AND `type`='" . $type . "'"
                 . "    AND `is_helpdesk`='1'";
         $result = $DB->query($sql);
         $list = [];
         if ($result) {
            while ($data = $DB->fetch_array($result)) {
               $list[$data['entities_id']] = $data['plugin_surveyticket_surveys_id'];
            }
         }
         if (isset($list[$entities_id])) {
            $plugin_surveyticket_surveys_id = $list[$entities_id];
         } else {
            foreach (getAncestorsOf('glpi_entities', $entities_id) as $ent_id) {
               if (isset($list[$ent_id])) {
                  $plugin_surveyticket_surveys_id = $list[$ent_id];
                  break;
               }
            }
         }
      }
      //if template exists
      if ($plugin_surveyticket_surveys_id > 0) {
         $psSurvey = new PluginSurveyticketSurvey();
         $psSurvey->getFromDB($plugin_surveyticket_surveys_id);
         if ($psSurvey->fields['is_active'] == 1) {
            $surveyTicket = new PluginSurveyticketTicket();

            if (isset($session['add']) && $session['add'] == false) {
               foreach ($session as $key => $value) {
                  if (!is_array($value)) {
                     $session[$key] = Html::cleanPostForTextArea($value);
                  }
               }
            }
            $surveyTicket->startSurvey($plugin_surveyticket_surveys_id, $session);
            unset($session);
            return true;
         }
      }
      unset($session);
      return false;
   }

   /**
    * Display survey
    *
    * @param $plugin_surveyticket_surveys_id
    * @param $session
    * @return string
    */
   function startSurvey($plugin_surveyticket_surveys_id, $session) {

      $psSurveyQuestion = new PluginSurveyticketSurveyQuestion();

      //list questions
      $a_questions = $psSurveyQuestion->find(
         "`plugin_surveyticket_surveys_id`='" . $plugin_surveyticket_surveys_id . "'", "`order`");

      echo "<input name='plugin_surveyticket_surveys_id' type='hidden' value='" . $plugin_surveyticket_surveys_id . "'/>";
      foreach ($a_questions as $data) {
         $this->displaySurvey($data['plugin_surveyticket_questions_id'], $plugin_surveyticket_surveys_id, $session);
      }
   }

   /**
    * Block to a question
    *
    * @param type $questions_id
    * @param type $plugin_surveyticket_surveys_id
    * @param $session
    * @param int $answer_id
    * @return string
    */
   function displaySurvey($questions_id, $plugin_surveyticket_surveys_id, $session, $answer_id = 0) {

      $psQuestion = new PluginSurveyticketQuestion();
      $data = $psQuestion->findQuestion($questions_id);

      if ($data != false) {
         //////////////                              Titre des questions alignÃ©s Ã  gauche                                      /////////////
         echo  "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='4'><span class='fa fa-comments-o fa-2x' aria-hidden='true'></span> ";
         if ($plugin_surveyticket_surveys_id == -1) {
            $answer = new PluginSurveyticketAnswer();
            $answer->getFromDB($answer_id);
            if ($answer->getFromDB($answer_id) && $answer->fields['mandatory']) {
               echo $data['name'] . " <span class='red'>&nbsp;*&nbsp;</span>";
            } else {
               echo $data['name'] . " ";
            }
         } else {
            $surveyquestion = new PluginSurveyticketSurveyQuestion();
            $surveyquestion->getFromDBByQuery("WHERE `plugin_surveyticket_questions_id` = " . $data['id'] . " AND `plugin_surveyticket_surveys_id` = " . $plugin_surveyticket_surveys_id);
            if ($surveyquestion->fields['mandatory']) {
               echo $data['name'] . " <span class='red'>&nbsp;*&nbsp;</span>";
            } else {
               echo $data['name'] . " ";
            }
         }
         Html::showToolTip($data['comment']);
         echo "</th>";
         echo "</tr>";
         //display answer for each question
         $array       = $this->displayAnswers($questions_id, $session);
         $nb_answer   = $array['count'];
         $tab_answers = $array['answers'];
         echo $array['bloc'];

         echo "</table>";
         $this->displayLink($questions_id, $session, $data, $nb_answer, $tab_answers);

         echo "<br/><div id='nextquestion" . $questions_id . "'></div>";
      }
   }

   /**
    * Display link
    *
    * @param $questions_id
    * @param $session
    * @param $psQuestion
    * @param $nb_answer
    * @param $answers
    * @return string
    */
   function displayLink($questions_id, $session, $psQuestion, $nb_answer, $answers) {
      global $CFG_GLPI;

      //javascript for links between issues
      if ($psQuestion['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion['type'] == PluginSurveyticketQuestion::YESNO) {

         $a_ids = array();
         //table id of all responses
         for ($i = 0; $i < $nb_answer; $i++) {
            if (isset($answers[$i])) {
               $a_ids[$answers[$i]] = 'question' . $questions_id . "-" . $i;
            }
         }
         $params = array("question" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "question" . $questions_id);
      } else if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion['type'])) {

         $a_ids = "realquestion" . $questions_id;
         $params = array("realquestion" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "realquestion" . $questions_id);
      } else {

         $a_ids = 'question' . $questions_id;
         $params = array("question" . $questions_id => '__VALUE__',
            'rand' => $questions_id,
            'myname' => "question" . $questions_id);
      }
      //script to detect if a change in response to a question
      if (PluginSurveyticketQuestion::isQuestionTypeText($psQuestion['type'])) {
         echo "<script type='text/javascript'>";
         echo Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids, false);
         echo "</script>";
      } elseif ($psQuestion['type'] != PluginSurveyticketQuestion::CHECKBOX) {
         echo "<script type='text/javascript'>";
         if (!is_array($a_ids)) {
            $a_ids = array($a_ids);
         }
         foreach ($a_ids as $key => $a_id) {
//            $params['answer_id'] = $key;
            $params['answer_id'] = '__VALUE__';
            echo Ajax::updateItemOnEventJsCode($a_id, "nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, array('change'), -1, -1, array(), FALSE);
         }
         echo "</script>";
         // Link to other issues loading the script
         if (!empty($session)) {
            $params['session'] = $session;
            echo "<script type='text/javascript'>";
            if ($psQuestion['type'] == PluginSurveyticketQuestion::DROPDOWN) {
               //dropdown load on the issue
               echo Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, "question" . $questions_id, false);
            } elseif ($psQuestion['type'] == PluginSurveyticketQuestion::RADIO || $psQuestion['type'] == PluginSurveyticketQuestion::YESNO) {
               //load on the selected response
               $psAnswer = new PluginSurveyticketAnswer();
               $result = $psAnswer->find("`plugin_surveyticket_questions_id` = " . $psQuestion['id']);
               $i = 0;
               foreach ($result as $data) {
                  $params['answer_id'] = $data['id'];
                  if (!empty($session[$questions_id]) && array_key_exists($data['id'], $session[$questions_id])) {
                     echo Ajax::updateItemJsCode("nextquestion" . $questions_id, $CFG_GLPI["root_doc"] . "/plugins/surveyticket/ajax/displaysurvey.php", $params, $a_ids[$data['id']], false);
                  }
                  $i++;
               }
            }
            echo "</script>";
         }
      }
   }

   /**
    * Response for each question
    *
    * @param type $questions_id
    * @param $session
    * @return type
    */
   function displayAnswers($questions_id, $session) {

      $psQuestion = new PluginSurveyticketQuestion();
      $psAnswer = new PluginSurveyticketAnswer();
      $psQuestion_Ticket = new PluginSurveyticketQuestion_Ticket();

      $a_answers = $psAnswer->findAnswers("`plugin_surveyticket_questions_id`='" . $questions_id . "'", "`order`");

      $disabled = '';
      if (isset($_GET['id']) && $_GET['id'] > 0) {
         $answers = $psQuestion_Ticket->find("`tickets_id`='".$_GET['id']."'");
         if (count($answers) > 0) {
            $disabled = 'disabled';
         }
      }

      $psQuestion->getFromDB($questions_id);
      $bloc = "";
      $answers = array();
      switch ($psQuestion->fields['type']) {
         case PluginSurveyticketQuestion::DROPDOWN:
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<th style='width:13%'></th>";
            $bloc .= "<td colspan='2'>";
            $bloc .= "<select name='question" . $questions_id . "' id='question" . $questions_id . "' ".$disabled.">";
            $bloc .= "<option>" . Dropdown::EMPTY_VALUE . "</option>";
            foreach ($a_answers as $data_answer) {
               $selected = False;
               if (isset($_GET['id']) && $_GET['id'] > 0) {
                  $answers = $psQuestion_Ticket->find("`plugin_surveyticket_questions_id`='".$questions_id."' "
                          . " AND `tickets_id`='".$_GET['id']."' and `value`='".$data_answer['id']."'");
                  if (count($answers) > 0) {
                     $selected = True;
                  }
               }
               if ($selected) {
                  $bloc .= "<option selected value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               } else if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<option value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               } else {
                  $bloc .= "<option selected value='" . $data_answer['id'] . "'>" . $psAnswer->getAnswer($data_answer) . "</option>";
               }
            }
            $bloc .= "</select>";
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::CHECKBOX :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               $bloc .= "<tr class='tab_bg_1'>";
               $bloc .= "<th style='width:13%'></th>";
               $bloc .= "<td width='40' >";
               $checked = False;
               if (isset($_GET['id']) && $_GET['id'] > 0) {
                  $answers = $psQuestion_Ticket->find("`plugin_surveyticket_questions_id`='".$questions_id."' "
                          . " AND `tickets_id`='".$_GET['id']."' and `value`='".$data_answer['id']."'");
                  if (count($answers) > 0) {
                     $checked = True;
                  }
               }
               if ($checked) {
                  $bloc .= "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked ".$disabled." />";
               } else if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' ".$disabled." />";
               } else {
                  $bloc .= "<input type='checkbox' name='question" . $questions_id . "[]' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked ".$disabled." />";
               }
               $bloc .= "</td>";
               $bloc .= "<td>";
               $bloc .= "<label for='question" . $questions_id . "-" . $i . "'>";
               $bloc .= $psAnswer->getAnswer($data_answer);
               $bloc .= "</label>";
               $bloc .= "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               $bloc .= "</tr>";
               $answers[$i] = $data_answer['id'];
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::RADIO :
         case PluginSurveyticketQuestion::YESNO :
            $i = 0;
            foreach ($a_answers as $data_answer) {
               $bloc .= "<tr class='tab_bg_1'>";
               $bloc .= "<th style='width:13%'></th>";
               $bloc .= "<td width='40'>";
               $checked = False;
               if (isset($_GET['id']) && $_GET['id'] > 0) {
                  $answers = $psQuestion_Ticket->find("`plugin_surveyticket_questions_id`='".$questions_id."' "
                          . " AND `tickets_id`='".$_GET['id']."' and `value`='".$data_answer['id']."'");
                  if (count($answers) > 0) {
                     $checked = True;
                  }
               }
               if ($checked) {
                  $bloc .= "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked ".$disabled." />";
               } else if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' ".$disabled." />";
               } else {
                  $bloc .= "<input type='radio' name='question" . $questions_id . "' id='question" . $questions_id . "-" . $i . "'
                     value='" . $data_answer['id'] . "' checked ".$disabled." />";
               }
               $bloc .= "</td>";
               $bloc .= "<td>";
               $bloc .= "<label for='question" . $questions_id . "-" . $i . "'>";
               $bloc .= $psAnswer->getAnswer($data_answer);
               $bloc .= "</label>";
               $bloc .= "</td>";
               if (empty($session) || empty($session[$questions_id][$data_answer['id']])) {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], NULL);
               } else {
                  $bloc .= $this->displayAnswertype($data_answer['answertype'], "text-" . $questions_id . "-" . $data_answer['id'], $session[$questions_id][$data_answer['id']]);
               }
               $bloc .= "</tr>";
               $answers[$i] = $data_answer['id'];
               $i++;
            }
            break;

         case PluginSurveyticketQuestion::DATE :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<th style='width:13%'></th>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id][$data_answer])) {
               $bloc .= Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id, "display" => false));
            } else {
               $bloc .= Html::showDateTimeField("question" . $questions_id, array('rand' => "question" . $questions_id, "display" => false, 'value' => $session[$questions_id]));
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" '.$disabled.' />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::INPUT :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<th style='width:13%'></th>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            $value = '';
            if (isset($_GET['id']) && $_GET['id'] > 0) {
               $answers = $psQuestion_Ticket->find("`plugin_surveyticket_questions_id`='".$questions_id."' "
                       . " AND `tickets_id`='".$_GET['id']."'");
               if (count($answers) > 0) {
                  $answer = current($answers);
                  $value = $answer['value'];
               }
            }
            if ($value != '') {
               $bloc .= '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="'.$value.'" size="100" '.$disabled.' />';
            } else if (empty($session) || empty($session[$questions_id])) {
               $bloc .= '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="" size="100" '.$disabled.' />';
            } else {
               $bloc .= '<input type="text" name="question' . $questions_id . '" id="question' . $questions_id . '" value="' . Html::cleanPostForTextArea($session[$questions_id]) . '" size="100" '.$disabled.' />';
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;

         case PluginSurveyticketQuestion::TEXTAREA :
            $bloc .= "<tr class='tab_bg_1'>";
            $bloc .= "<th style='width:13%'></th>";
            $bloc .= "<td colspan='2'>";
            $data_answer = current($a_answers);
            if (empty($session) || empty($session[$questions_id])) {
               $bloc .= '<textarea name="question' . $questions_id . '"  cols="90" rows="4" '.$disabled.'></textarea>';
            } else {
               $bloc .= '<textarea name="question' . $questions_id . '"  cols="90" rows="4" '.$disabled.'>' . Html::cleanPostForTextArea($session[$questions_id]) . '</textarea>';
            }
            $bloc .= '<input type="hidden" name="realquestion' . $questions_id . '" id="realquestion' . $questions_id . '" value="' . $data_answer['id'] . '" />';
            $bloc .= "</td>";
            $bloc .= "</tr>";
            break;
      }
      return array("count" => count($a_answers), "bloc" => $bloc, 'answers' => $answers);
   }

   /**
    * Display answer
    *
    * @param $type
    * @param $name
    * @param $session
    * @return string
    */
   function displayAnswertype($type, $name, $session) {

      $bloc = "<td>";
      if ($type != '') {
         //echo "<tr class='tab_bg_1'>";
         switch ($type) {

            case 'shorttext':
               if ($session == NULL) {
                  $bloc .= "<input type='text' name='" . $name . "' value='' size='71'/>";
               } else {
                  $bloc .= "<input type='text' name='" . $name . "' value='" . Html::cleanPostForTextArea($session) . "' size='71'/>";
               }
               break;

            case 'longtext':
               if ($session == NULL) {
                  $bloc .= "<textarea name='" . $name . "' cols='100' rows='4'></textarea>";
               } else {
                  $bloc .= "<textarea name='" . $name . "' cols='100' rows='4'>".Html::cleanPostForTextArea($session)."</textarea>";
               }
               break;

            case 'date':
               if ($session == NULL) {
                  $bloc .= Html::showDateTimeField($name, array("display" => false));
               } else {
                  $bloc .= Html::showDateTimeField($name, array("display" => false, 'value' => $session));
               }
               break;

            case 'number':
               if ($session == NULL) {
                  $bloc .= Dropdown::showNumber($name, array("display" => false));
               } else {
                  $bloc .= Dropdown::showNumber($name, array("display" => false, 'value' => $session));
               }
               break;
         }
      }
      $bloc .= "</td>";
      return $bloc;
   }

   /**
    * Checking the mandatory questionnaire responses
    *
    * @param Ticket $ticket
    * @return bool
    */
   static function checkMandatoryFields(Ticket $ticket) {
      $msg     = array();
      $checkKo = false;
      $surveyquestion = new PluginSurveyticketSurveyQuestion();
      $a_questions    = $surveyquestion->find("`plugin_surveyticket_surveys_id`='" . $ticket->input['plugin_surveyticket_surveys_id'] . "'", "`order`");
      foreach ($a_questions as $data) {
         $reponse = self::checkQuestion(array('msg'=> $msg, 'checkKo' => $checkKo, 'data' => $data, 'ticket' => $ticket->input));
         $msg = $reponse['msg'];
         $checkKo = $reponse['checkKo'];
      }
      if ($checkKo) {
         Session::addMessageAfterRedirect(sprintf(__("Mandatory questions are not filled. Please correct: %s", 'surveyticket'), implode(', ', $msg)), false, ERROR);
         return false;
      }
      return true;
   }

   /**
    * Checking if mandatory question
    *
    * @param $param
    * @return array
    */
   static function checkQuestion($param) {
      $data    = $param['data'];
      $msg     = $param['msg'];
      $checkKo = $param['checkKo'];
      $ticket  = $param['ticket'];

      $psQuestion = new PluginSurveyticketQuestion();
      $psQuestion->getFromDB($data['plugin_surveyticket_questions_id']);
      if (isset($data['mandatory']) && $data['mandatory']) {
         if (!isset($ticket['question' . $psQuestion->fields['id']]) || empty($ticket['question' . $psQuestion->fields['id']]) || $ticket['question' . $psQuestion->fields['id']] == '-----'){
            $msg[]   = $psQuestion->fields['name'];
            $checkKo = true;
         }
      }
      if (isset($ticket['question' . $psQuestion->fields['id']])) {
         $psAnswer = new PluginSurveyticketAnswer();
         if (!PluginSurveyticketQuestion::isQuestionTypeText($psQuestion->fields['type']) && !is_array($ticket['question' . $psQuestion->fields['id']])) {
            if ($psAnswer->getFromDB($ticket['question' . $psQuestion->fields['id']])) {
               if ($psAnswer->fields['link'] > 0) {
                  $reponse = self::checkQuestion(array('msg'     => $msg,
                        'checkKo' => $checkKo,
                        'ticket'  => $ticket,
                        'data'    => array('plugin_surveyticket_questions_id'     => $psAnswer->fields['link'],
                           'mandatory'                            => $psAnswer->fields['mandatory'],
                           'old_plugin_surveyticket_questions_id' => $data['plugin_surveyticket_questions_id'])));
                  $msg     = $reponse['msg'];
                  $checkKo = $reponse['checkKo'];
               }
            }
         }
      }
      return array('msg' => $msg, 'checkKo' => $checkKo);
   }

   /**
    * Post input datas for adding the ticket
    *
    * @param Ticket $ticket
    * @return bool|Ticket
    */
   static function preAddTicket(Ticket $ticket) {
      if($_SESSION['glpiactiveprofile']['interface'] == 'central'){
         $response = self::isSurveyTicket($ticket->fields['type'], $ticket->fields['itilcategories_id'], $ticket->fields['entities_id'], $_SESSION['glpiactiveprofile']['interface']);
      }else{
         $response = self::isSurveyTicket($ticket->input['type'], $ticket->input['itilcategories_id'], $ticket->input['entities_id'], $_SESSION['glpiactiveprofile']['interface']);
      }
      if (!$response) {
         return true;
      }
      self::setSessions($ticket->input, true);
      if (self::checkMandatoryFields($ticket)) {
         //Recovery of the survey to put in the content of the ticket
         $psQuestion        = new PluginSurveyticketQuestion();
         $psAnswer          = new PluginSurveyticketAnswer();
         $description = '';
         $ticket->input['_question_answer'] = [];
         foreach ($ticket->input as $question => $answer) {
            if (preg_match("/^question/", $question) && !preg_match("/^realquestion/", $question)) {
               $dataQuestion = $psQuestion->findQuestion(str_replace("question", "", $question));
               $input = [
                   'tickets_id' => 0,
                   'plugin_surveyticket_questions_id' => $dataQuestion['id']
               ];

               if (is_array($answer)) {
                  // Checkbox
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $dataQuestion['name'] . "\n";
                  foreach ($answer as $answers_id) {
//                     if ($psAnswer->getFromDB($answers_id)) {
                     $dataAnswer = $psAnswer->findAnswer($answers_id);
                     if ($dataAnswer != false) {
                        $input['value'] = $answers_id;
                        $ticket->input['_question_answer'][] = $input;
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $dataAnswer['name'] . "\n";
                        $qid = str_replace("question", "", $question);
                        if (isset($ticket->input["text-" . $qid . "-" . $answers_id])
                           AND $ticket->input["text-" . $qid . "-" . $answers_id] != '') {
                           $description .= __('Text', 'surveyticket') . " : " . $ticket->input["text-" . $qid . "-" . $answers_id] . "\n";
                        }
                     }
                  }
                  $description .= "\n";
                  unset($ticket->input[$question]);
               } else {
                  $real = 0;
                  if (isset($ticket->input['realquestion' . (str_replace("question", "", $question))]) && $ticket->input['realquestion' . (str_replace("question", "", $question))] != '') {
                     $realanswer = $answer;
                     $answer     = $ticket->input['realquestion' . str_replace("question", "", $question)];
                     $real       = 1;
                  }
                  $description .= "===========================================================================\n";
                  $description .= _n('Question', 'Questions', 1, 'surveyticket') . " : " . $dataQuestion['name'] . "\n";
                  //check text question type
                  if (!PluginSurveyticketQuestion::isQuestionTypeText($dataQuestion['type'])) {
                     if ($real == 1) {
                        $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $realanswer . "\n";
                        $input['value'] = $realanswer;
                        $ticket->input['_question_answer'][] = $input;
                     } else {
                        //check if it is an id
                        if(is_numeric($answer)){
                           $dataAnswer = $psAnswer->findAnswer($answer);
                           $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $dataAnswer['id'] . "\n";
                           $input['value'] = $dataAnswer['id'];
                           $ticket->input['_question_answer'][] = $input;
                        } else {
                           $description .= _n('Answer', 'Answers', 1, 'surveyticket') . " : " . $answer . "\n";
                           $input['value'] = $answer;
                           $ticket->input['_question_answer'][] = $input;
                        }
                     }
                     $qid = str_replace("question", "", $question);
                     if (isset($ticket->input["text-" . $qid . "-" . $answer])
                        AND $ticket->input["text-" . $qid . "-" . $answer] != '') {
                        $description .= __('Text', 'surveyticket') . " : " . $ticket->input["text-" . $qid . "-" . $answer] . "\n";
                     }
                     $description .= "\n";
                     unset($ticket->input[$question]);
                  } else {
                     $description .= __('Text', 'surveyticket') . " : " . str_replace('\r', "", $answer) . "\n";
                     $description .= "\n";
                     $input['value'] = $answer;
                     $ticket->input['_question_answer'][] = $input;
                     unset($ticket->input[$question]);
                  }
               }
            }
            if ($description != '') {
               $ticket->input['content'] = $description;
            }
         }
      } else {
         $ticket->input =array();
      }

      return $ticket;
   }

   /**
    * Empty the session variable to not refill the old values
    * @param Ticket $ticket
    */
   static function postAddTicket(Ticket $ticket) {
      $psQuestion_Ticket = new PluginSurveyticketQuestion_Ticket();
      foreach ($ticket->input['_question_answer'] as $input) {
         $input['tickets_id'] = $ticket->fields['id'];
         $psQuestion_Ticket->add($input);
      }


      if (isset($_SESSION['glpi_plugin_surveyticket_ticket'])) {
         unset($_SESSION['glpi_plugin_surveyticket_ticket']);
      }
   }


   static function postForm($params) {
      if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
         if ($params['item']->getType() == 'Ticket') {
            $psTicket = new PluginSurveyticketTicket();
            $psTicket->getSurveyTicket(Ticket::INCIDENT_TYPE, $params['item']->fields['itilcategories_id'], $params['item']->fields['entities_id'], 'central');
         }
      }
   }
}
