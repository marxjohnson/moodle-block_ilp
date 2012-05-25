<?php
/**
 * Creates a comment on a report entry
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

//get the id of the user that the comment relates to
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//if set get the id of the report entry
$entry_id	= $PARSER->required_param('entry_id',PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$comment_id = $PARSER->optional_param('comment_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_entrycomment.php",array('report_id'=>$report_id,'user_id'=>$user_id,'course_id'=>$course_id,'entry_id'=>$entry_id,'comment_id'=>$comment_id,'selectedtab'=>$selectedtab,'tabitem'=>$tabitem));

// instantiate the db
$dbc = new ilp_db();

//get the report
$report		=	$dbc->get_report_by_id($report_id);

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status)) {
    header('HTTP/1.1 400 Bad Request');
    $output = get_string('reportnotfouund','block_ilp');
}

//get the report entry
$entry		=	$dbc->get_entry_by_id($entry_id);

//if the report entry is not found throw an error
if (empty($entry) ) {
    header('HTTP/1.1 400 Bad Request');
    $output = get_string('entrynotfouund','block_ilp');
}


//check if the any of the users roles in the
//current context has the create report capability for this report

if (empty($comment_id) && empty($access_report_addcomment))	{
    header('HTTP/1.1 403 Forbidden');
    //the user doesnt have the capability to create a comment
    $output = get_string('userdoesnothavecreatecapability','block_ilp');
}

if (!empty($comment_id) && empty($access_report_editcomment))	{
    header('HTTP/1.1 403 Forbidden');
    //the user doesnt have the capability to edit this type of report entry
    $output = get_string('userdoesnothaveeditcapability','block_ilp');
}

if (empty($report->comments))	{
    header('HTTP/1.1 403 Forbidden');
    //the current report does not allow comments
    $output = get_string('commentsnotallowed','block_ilp');
}


//require the entrycomment_mform so we can display the report
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_entrycomment_mform.php');

$mform	= new	edit_entrycomment_mform($report_id,$entry_id,$user_id,$course_id,$comment_id,$selectedtab,$tabitem);


//was the form submitted?
// has the form been submitted?
if ($formdata = $mform->get_data()) {

    // process the data
    $success = $mform->process_data($formdata);

    //if saving the data was not successful
    if ($success) {
       //notify the user that a comment has been made on one of their report entries
        if ($USER->id != $entry->user_id)   {
            $reportsviewtab             =   $dbc->get_tab_plugin_by_name('ilp_dashboard_reports_tab');
            $reportstaburl              =   (!empty($reportsviewtab)) ?  "&selectedtab={$reportsviewtab->id}&tabitem={$reportsviewtab->id}:{$report->id}" : "";

            $message                    =   new stdClass();
            $message->component         =   'block_ilp';
            $message->name              =   'ilp_comment';
            $message->subject           =   get_string('newreportcomment','block_ilp',$report);;
            $message->userfrom          =   $dbc->get_user_by_id($USER->id);
            $message->userto            =   $dbc->get_user_by_id($entry->user_id);
            $message->fullmessage       =   get_string('newreportcomment','block_ilp',$report);
            $message->fullmessageformat =   FORMAT_PLAIN;
            $message->contexturl        =   $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$entry->user_id}{$reportstaburl}";
            $message->contexturlname    =   get_string('viewreport','block_ilp');
            $message->smallmessage      =   $message->fullmessage;
            $message->fullmessagehtml   =   $message->fullmessage;

            if (stripos($CFG->release,"2.") !== false) {
                message_send($message);
            } else {
                require_once($CFG->dirroot.'/message/lib.php');
                message_post_message($message->userfrom, $message->userto,$message->fullmessage,$message->fullmessageformat,'direct');
            }
        }

        $tabclass = $dbc->get_tab_plugin_by_id($selectedtab)->name;
        require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/'.$tabclass.'.php');
        $tab = new $tabclass($user_id, $course_id);
        $output = $tab->display($tabitem);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        //print an error message
        $output = get_string('commentcreationerror', 'block_ilp');
    }
} else {
    header('HTTP/1.1 400 Invalid Request');
    $output = 'No form data found';
}

echo json_encode(array('output' => $output));
