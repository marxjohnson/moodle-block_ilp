<?php

/**
 * Deletes a report
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

// Include the report permissions file
require_once($CFG->dirroot.'/blocks/ilp/report_permissions.php');

//if set get the id of the report 
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	


//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);


//if set get the id of the report entry to be edited
$entry_id	= $PARSER->required_param('entry_id',PARAM_INT);

//if set get the id of the report entry to be edited
$comment_id	= $PARSER->required_param('comment_id',PARAM_INT);


//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

// instantiate the db
$dbc = new ilp_db();

try {
    //get the report 
    $report		=	$dbc->get_report_by_id($report_id);

    //if the report is not found throw an error of if the report has a status of disabled
    if (empty($report) || empty($report->status)) {
        throw new Exception(get_string('reportnotfouund','block_ilp'), 404);
    }

    //get the entry 
    $entry = $dbc->get_entry_by_id($entry_id);

    //if the report is not found throw an error of if the report has a status of disabled
    if (empty($entry)) {
        throw new Exception(get_string('entrynotfouund','block_ilp'), 404);
    }

    //check if the user has the delete record capability
    if (empty($access_report_deletecomment))	{
        //the user doesnt have the capability to create this type of report entry
        throw new Exception(get_string('userdoesnothavedeletecapability','block_ilp'), 403);
    }

    // instantiate the db
    $dbc = new ilp_db();

    if (!$dbc->delete_comment_by_id($comment_id)) {
        throw new Exception('Comment Not Deleted', 500);
    }

    $tabclass = $dbc->get_tab_plugin_by_id($selectedtab)->name;
    require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/'.$tabclass.'.php');
    $tab = new $tabclass($user_id, $course_id);
    $output = $tab->display($tabitem);

} catch (Exception $e) {
    header('HTTP/1.1 '.$e->getCode());
    $output = $e->getMessage();
}

echo json_encode(array('output' => $output));
