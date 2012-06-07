<?php

/**
 * Creates an entry for an report
 *
 * @copyright &copy; 2011 University of London Computer Centre, 2012 Taunton's College UK
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
$report_id    = $PARSER->required_param('report_id',PARAM_INT);


//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);


//if set get the id of the report entry to be edited
$entry_id    = $PARSER->optional_param('entry_id',NULL,PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

$state = 0;
$output = '';
$script = '';

// instantiate the db
$dbc = new ilp_db();

//get the report
$report    =    $dbc->get_report_by_id($report_id);
try {
    //if the report is not found throw an error of if the report has a status of disabled
    if (empty($report) || empty($report->status) || !empty($report->deleted)) {
        throw new Exception(get_string('reportnotfouund','block_ilp'), 404);
    }


    //check if the any of the users roles in the
    //current context has the create report capability for this report

    if (empty($access_report_createreports))    {
            //the user doesnt have the capability to create this type of report entry

        throw new Exception(get_string('userdoesnothavecreatecapability','block_ilp'), 403);
    }


    if (!empty($entry_id))    {
            if (empty($access_report_editreports))    {
                    //the user doesnt have the capability to edit this type of report entry

                throw new Exception(get_string('userdoesnothavedeletecapability','block_ilp'), 403);
            }
    }

    $reportfields    =    $dbc->get_report_fields_by_position($report_id);

    //we will only attempt to display a report if there are elements in the
    //form. if not we will send the user back to the dashboard
    if (empty($reportfields)) {
            //send the user back to the dashboard page telling them that the report is not ready for display
        throw new Exception(get_string("reportnotready", 'block_ilp'), 501);
    }

    //require the reportentry_mform so we can display the report
    require_once($CFG->dirroot.'/blocks/ilp/classes/forms/reportentry_mform.php');

    $mform = new report_entry_mform($report_id,$user_id,$entry_id,$course_id);

    //was the form submitted?
    // has the form been submitted?
    if($mform->is_submitted()) {
        // check the validation rules
        if($mform->is_validated()) {

            //get the form data submitted
            $formdata = $mform->get_data();
            $state = 1;

            // process the data
            $success = $mform->process_data($formdata);

            //if saving the data was not successful
            if (!$success) {
                            //print an error message
                throw new Exception(get_string("entrycreationerror", 'block_ilp'), 500);
            }
        } else {
            throw new Exception('', 400);
        }
    }


    if (!empty($entry_id)) {
            //create a entry_data object this will hold the data that will be passed to the form
        $entry_data    =    new stdClass();

            //get the main entry record
            $entry    =    $dbc->get_entry_by_id($entry_id);

            if (!empty($entry))     {
                    //check if the maximum edit field has been set for this report
                    if (!empty($report->maxedit))     {
                        //calculate the age of the report entry
                        $entryage = time() - $entry->timecreated;

                            //if the entry is older than the max editing time
                            //then return the user to the
                        if ($entryage > $CFG->maxeditingtime) {
                            throw new Exception(get_string("maxeditexceed", 'block_ilp'), 423);
                        }
                    }

                    //get all of the fields in the current report, they will be returned in order as
                    //no position has been specified
                    $reportfields = $dbc->get_report_fields_by_position($report_id);

                    foreach ($reportfields as $field) {

                        //get the plugin record that for the plugin
                            $pluginrecord    =    $dbc->get_plugin_by_id($field->plugin_id);

                            //take the name field from the plugin as it will be used to call the instantiate the plugin class
                            $classname = $pluginrecord->name;

                            // include the class for the plugin
                            include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");

                            if (!class_exists($classname)) {
                                throw new Exception(get_string('noclassforplugin', 'block_ilp', '', $pluginrecord->name), 404);
                            }

                            //instantiate the plugin class
                            $pluginclass = new $classname();

                            $pluginclass->load($field->id);

                            //create the fieldname
                            $fieldname = $field->id."_field";

                            $pluginclass->load($field->id);

                            //call the plugin class entry data method
                            $pluginclass->entry_data($field->id,$entry_id,$entry_data);
                    }

                    //loop through the plugins and get the data for each one
                    $mform->set_data($entry_data);
            }
    }

    if ($state == 0) {

        // If there's no info about the tab selected, we won't be able to refresh the tab properly,
        // so redirect to the non-ajax page
        if (empty($selectedtab) || empty($tabitem)) {
            $params = array(
                'report_id' => $report_id,
                'user_id' => $user_id,
                'entry_id' => $entry_id,
                'course_id' => $course_id
            );
            $url = new moodle_url('/blocks/ilp/actions/edit_reportentry.php', $params);
            throw new Exception($url->out(false), 302);
        }

        ob_start();
        $mform->display();
        $form = ob_get_clean();
        if (strpos($form, '</script>') !== false) {
            $outputparts = explode('</script>', $form);
            $output = $outputparts[1];
            $script = str_replace('<script type="text/javascript">', '', $outputparts[0]);
        } else {
            $output = $form;
        }

        // Now it gets a bit tricky, we need to get the libraries and init calls for any Javascript used
        // by the form element plugins.
        $headcode = $PAGE->requires->get_head_code($PAGE, $OUTPUT);
        $loadpos = strpos($headcode, 'M.yui.loader');
        $cfgpos = strpos($headcode, 'M.cfg');
        $script .= substr($headcode, $loadpos, $cfgpos-$loadpos);
        $endcode = $PAGE->requires->get_end_code();
        $script .= preg_replace('/<\/?(script|link)[^>]*>/', '', $endcode);
    } else {

        $tabclass = $dbc->get_tab_plugin_by_id($selectedtab)->name;
        require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/'.$tabclass.'.php');
        $tab = new $tabclass($user_id, $course_id);
        $output = $tab->display($tabitem);
    }

} catch (Exception $e) {
    header('HTTP/1.1 '.$e->getCode());
    $output = $e->getMessage();
}

echo json_encode(array('state' => $state, 'output' => $output, 'script' => $script));
