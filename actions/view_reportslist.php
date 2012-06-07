<?php
if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}

//get the report
$report	= $dbc->get_report_by_id($report_id);

//get all of the fields in the current report, they will be returned in order as
//no position has been specified
$reportfields =	$dbc->get_report_fields_by_position($report_id);

//get all instances of this report for the user
$entries = $dbc->get_user_report_entries($report_id, $user_id);

//does this report give user the ability to add comments
$has_comments = (!empty($report->comments)) ? true : false;

//does this report allow users to say it is related to a particular course
$courserelated = $dbc->has_plugin_field($report->id, 'ilp_element_plugin_course');

//this will hold the ids of fields that we dont want to display
$dontdisplay = array();

if ($courserelated) {
    //the should not be anymore than one of these fields in a report
    foreach ($courserelated as $cr) {
        $dontdisplay[] = $cr->id;
    }
}

$deadline = $dbc->has_plugin_field($report->id, 'ilp_element_date_deadline');

if ($deadline) {
    // the should not be anymore than one of these fields in a report
    foreach ($deadline as $d) {
        $dontdisplay[] = $d->id;
    }
}

//require the view_reportlist.html page
require_once($CFG->dirroot.'/blocks/ilp/views/view_reportslist.html');

?>
