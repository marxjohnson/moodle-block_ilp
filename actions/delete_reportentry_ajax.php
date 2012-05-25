<?php

/**
 * Deletes a report
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
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

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status)) {
    header('HTTP/1.1 400 Bad Request');
    $output = get_string('reportnotfouund','block_ilp');
}

//get the entry 
$entry		=	$dbc->get_entry_by_id($entry_id);

//if the report is not found throw an error of if the report has a status of disabled
if (empty($entry)) {
    header('HTTP/1.1 404 Not Found');
    $output = get_string('entrynotfouund','block_ilp');
}

if (empty($report->frequency))	{
    //entries can only be deleted from reports that allow multiple entries
    header('HTTP/1.1 400 Bad Request');
    $output = get_string('entrycannotbedeleted','block_ilp');
} 
 
//check if the user has the delete record capability
if (empty($access_report_deletereports))	{
    //the user doesnt have the capability to create this type of report entry
    header('HTTP/1.1 403 Forbidden');
    $output = get_string('userdoesnothavedeletecapability','block_ilp');
}



// instantiate the db
$dbc = new ilp_db();

//get all of the fields in the current report, they will be returned in order as
//no position has been specified
$reportfields		=	$dbc->get_report_fields_by_position($report_id);
			
if (!empty($reportfields))	{ 
	foreach ($reportfields as $field) {
		//get the plugin record that for the plugin 
		$pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);
				
		$dbc->delete_element_record_by_id($pluginrecord->tablename.'_ent',$field->id);
	}
}

$status = $dbc->delete_entry_by_id($entry_id);

if ($status) {
    $tabclass = $dbc->get_tab_plugin_by_id($selectedtab)->name;
    require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/'.$tabclass.'.php');
    $tab = new $tabclass($user_id, $course_id);
    $output = $tab->display($tabitem);
} else {
    header("HTTP/1.1 500 Internal Server Error");
    $output = 'Report not Deleted';
}

echo json_encode(array('output' => $output));
