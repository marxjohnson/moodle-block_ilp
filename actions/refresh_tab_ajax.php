<?php

/**
 * Returns the HTML contents for the current tab
 *
 * @copyright &copy; 2011 University of London Computer Centre, 2012 Taunton's College, UK
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk, Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

// instantiate the db
$dbc = new ilp_db();

try {
    $tabclass = $dbc->get_tab_plugin_by_id($selectedtab)->name;
    require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/'.$tabclass.'.php');
    $tab = new $tabclass($user_id, $course_id);
    $output = $tab->display($tabitem);
} catch (moodle_exception $e) {
    header('HTTP/1.1 500');
    $output = $e->getMessage();
}

echo json_encode(array('output' => $output));
