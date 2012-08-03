<?php
/**
 * @copyright 2012 Taunton's College UK
 * @author Mark Johnson <mark.johnson@tauntons.ac.uk>
 * @licence GNU General Public Licence version 3
 * @package ILP
 */

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

class ilp_dashboard_timeline_tab extends ilp_dashboard_tab {

    public $student_id;
    public $filepath;
    public $linkurl;
    public $selectedtab;
    public $role_ids;
    public $capability;
    public $report_ids;


    function __construct($student_id=null,$course_id=null)    {
        global     $CFG,$USER,$PAGE;

        //$this->linkurl                =    $CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id."&course_id={$course_id}";

        $this->linkurl = new moodle_url('/blocks/ilp/actions/view_main.php', array('user_id' => $student_id, 'course_id' => $course_id));

        $this->student_id = $student_id;

        $this->course_id = $course_id;

        $this->report_ids = unserialize(get_config('block_ilp', 'ilp_dashboard_timeline_tab_reports'));
        //call the parent constructor
        parent::__construct();
    }

    /**
     * Return the text to be displayed on the tab
     */
    function display_name()    {
        return get_string('ilp_dashboard_timeline_tab_name','block_ilp');
    }

    /**
     * Returns the content to be displayed
     *
     * @param    string $selectedtab the tab that has been selected this variable
     * this variable should be used to determined what to display
     *
     * @return none
      */
    function display($selectedtab = null) {
        global $CFG, $PAGE, $USER, $OUTPUT, $PARSER;

        $hw = 'html_writer';
        $pluginoutput = "";

        if ($this->dbc->get_user_by_id($this->student_id)) {

            //get the selecttab param if has been set
            $this->selectedtab = $PARSER->optional_param('selectedtab', $this->plugin_id, PARAM_INT);

            //get the tabitem param if has been set
            $this->tabitem = $PARSER->optional_param('tabitem', $this->plugin_id, PARAM_CLEAN);

            $showall = $PARSER->optional_param('showall', false, PARAM_BOOL);

            $pluginoutput = $OUTPUT->heading($this->display_name(), 2);

            $reports = array();
            foreach ($this->report_ids as $report_id) {
                $reports[$report_id] = $this->dbc->get_report_by_id($report_id);
            }

            if ($reports) {

                $addbuttons = array();
                $entrycount = 0;

                foreach ($reports as $report) {
                    $report->icon = (!empty($report->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report->id=".$report->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";
                    // $report->stateselector = $this->stateselector($report->id);

                //output the print icon
                // echo "{$stateselector}<div class='entry_floatright'><a href='#' onclick='M.ilp_standard_functions.printfunction()' ><img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/print_icon_med.png' alt='".get_string("print","block_ilp")."' class='ilp_print_icon' width='32px' height='32px' ></a></div>";

                    //get all of the fields in the current report, they will be returned in order as
                    //no position has been specified
                    $report->fields = $this->dbc->get_report_fields_by_position($report->id);

                    //does this report give user the ability to add comments
                    $report->has_comments = (!empty($report->comments)) ? true : false;

                    // Does this report allow multiple entries?
                    $report->has_multiple = $report->frequency;

                    //this will hold the ids of fields that we dont want to display
                    $report->dontdisplay = array();

                    //does this report allow users to say it is related to a particular course
                    $report->has_courserelated = (!$this->dbc->has_plugin_field($report->id,'ilp_element_plugin_course')) ? false : true;

                    if (!empty($report->has_courserelated)) {
                        $courserelated = $this->dbc->has_plugin_field($report->id, 'ilp_element_plugin_course');
                        //the should not be anymore than one of these fields in a report
                        foreach ($courserelated as $cr) {
                            $report->dontdisplay[] = $cr->id;
                            $courserelatedfield_id = $cr->id;
                        }
                    }

                    $report->has_datedeadline = (bool)$this->dbc->has_plugin_field($report->id, 'ilp_element_plugin_date_deadline');
                    if ($report->has_datedeadline) {
                        $deadline = $this->dbc->has_plugin_field($report->id,'ilp_element_plugin_date_deadline');
                            //the should not be anymore than one of these fields in a report
                        foreach ($deadline as $d) {
                            $report->dontdisplay[] = $d->id;
                        }
                    }

                    // Does the report have a radio button called "private"?
                    $report->has_private = false;
                    if ($radio = $this->dbc->has_plugin_field($report->id, 'ilp_element_plugin_rdo')) {
                        foreach ($radio as $r) {
                            if (stripos($r->label, 'private') !== false) {
                                $report->has_private = $r;
                                $report->dontdisplay[] = $r->id;
                            }
                        }
                    }



                    //get all of the users roles in the current context and save the id of the roles into
                    //an array
                    $role_ids = array();
                    $report->access = array();
                    $authuserrole = $this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
                    if (!empty($authuserrole)) $role_ids[] = $authuserrole->id;

                    if ($roles = get_user_roles($PAGE->context, $USER->id)) {
                        foreach ($roles as $role) {
                            $role_ids[]    = $role->roleid;
                        }
                    }



                    $report->access['addreports'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:addreport');
                    if (!empty($capability)) {
                        $report->access['addreports'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the edit report capability for the report
                    $report->access['editreports'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:editreport');
                    if (!empty($capability)) {
                        $report->access['editreports'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the delete report capability for the report
                    $report->access['deletereports'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:deletereport');
                    if (!empty($capability)) {
                        $report->access['deletereports'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the add comment capability for the report
                    $report->access['addcomment'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:addcomment');
                    if (!empty($capability)) {
                        $report->access['addcomment'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the edit comment capability for the report
                    $report->access['editcomment'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:editcomment');
                    if (!empty($capability)) {
                        $report->access['editcomment'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the add comment capability for the report
                    $report->access['deletecomment'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:deletecomment');
                    if (!empty($capability)) {
                        $report->access['deletecomment'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //find out if the current user has the edit comment capability for the report
                    $report->access['viewcomment'] = false;
                    $capability = $this->dbc->get_capability_by_name('block/ilp:viewcomment');
                    if (!empty($capability)) {
                        $report->access['viewcomment'] = $this->dbc->has_report_permission($report->id,$role_ids,$capability->id);
                    }

                    //check to see whether the user can delete the report@s entry
                    $report->candelete = (!empty($report->frequency) && !empty($report->access['deletereports'])) ? true : false;

                    //get all of the entries for this report
                    $report->entries = $this->dbc->get_user_report_entries($report->id,$this->student_id);

                    $entrycount += count($report->entries);

                    $lastentry = $this->dbc->get_lastupdatedentry($report->id, $this->student_id);

                    if ($report->has_multiple && $report->access['addreports']) {
                        $addparams = array(
                            'user_id' => $this->student_id,
                            'report_id' => $report->id,
                            'course_id' => $this->course_id,
                            'selectedtab' => $this->selectedtab,
                            'tabitem' => $this->tabitem
                        );
                        $url = new moodle_url('/blocks/ilp/actions/edit_reportentry.php', $addparams);
                        $addbuttons[$url->out(false)] = get_string('addnew', 'block_ilp').' '.$report->name;
                    } else if (!$report->has_multiple && $lastentry && $report->access['editreports']) {
                        $editparams = array(
                            'user_id' => $this->student_id,
                            'report_id' => $report->id,
                            'course_id' => $this->course_id,
                            'entry_id' => $lastentry->id,
                            'selectedtab' => $this->selectedtab,
                            'tabitem' => $this->tabitem
                        );
                        $url = new moodle_url('/blocks/ilp/actions/edit_reportentry.php', $editparams);
                        $addbuttons[$url->out()] = get_string('edit').' '.$report->name;
                    }
                }//end new if

                // Display "Add" buttons
                $pluginoutput .= $this->render_addbuttons($addbuttons);
                $pluginoutput .= $OUTPUT->container('', 'clearfix', 'edit_reportentry_form');
                $pluginoutput .= $OUTPUT->container('', '', 'edit_reportentry_form_container');

                $entries = $this->collate_entries_by_date($reports, $showall);
                //create the entries list var that will hold the entry information
                $entrieslist = array();

                if (!empty($entries)) {
                    foreach ($entries as $count => $entry) {

                        //TODO: is there a better way of doing this?
                        //I am currently looping through each of the fields in the report and get the data for it
                        //by using the plugin class. I do this for two reasons it may lock the database for less time then
                        //making a large sql query and 2 it will also allow for plugins which return multiple values. However
                        //I am not naive enough to think there is not a better way!

                        $entry->data = new stdClass();

                        //get the creator of the entry
                        $creator = $this->dbc->get_user_by_id($entry->creator_id);

                        //get comments for this entry
                        $entry->comments = $this->dbc->get_entry_comments($entry->id);

                        //
                        $entry->data->creator = (!empty($creator)) ? fullname($creator) : get_string('notfound','block_ilp');
                        $entry->data->created = userdate($entry->timecreated);
                        $entry->data->modified = userdate($entry->timemodified);
                        $entry->data->user_id = $entry->user_id;
                        $entry->data->entry_id = $entry->id;

                        if ($entry->report->has_courserelated) {
                            $coursename = false;
                            $crfield = $this->dbc->get_report_coursefield($entry->id, $courserelatedfield_id);
                            if (empty($crfield) || empty($crfield->value)) {
                                $coursename = get_string('allcourses','block_ilp');
                            } else if ($crfield->value == '-1') {
                                $coursename = get_string('personal','block_ilp');
                            } else {
                                $crc = $this->dbc->get_course_by_id($crfield->value);
                                if (!empty($crc)) $coursename = $crc->shortname;
                            }
                            $entry->data->coursename = (!empty($coursename)) ? $coursename : '';
                        }

                        foreach ($entry->report->fields as $field) {

                            //get the plugin record that for the plugin
                            $pluginrecord = $this->dbc->get_plugin_by_id($field->plugin_id);

                            //take the name field from the plugin as it will be used to call the instantiate the plugin class
                            $classname = $pluginrecord->name;

                            // include the class for the plugin
                            include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");

                            if (!class_exists($classname)) {
                                 print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                            }

                            // instantiate the plugin class
                            $pluginclass = new $classname();

                            if ($pluginclass->is_viewable() != false) {
                                $pluginclass->load($field->id);

                                //call the plugin class entry data method
                                $pluginclass->view_data($field->id, $entry->id, $entry->data);
                            } else {
                                $entry->report->dontdisplay[] = $field->id;
                            }

                        }
                        $pluginoutput .= $this->render_entry($entry);
                        if ($count == 19) {
                            $pluginoutput .= html_writer::tag('a', '', array('name' => 'showall'));
                        }
                    }
                    if (!$showall && $entrycount > 20) {
                        $strparams = (object)array('count' => $entrycount-20);
                        $strshowall = get_string(get_class().'_showall', 'block_ilp', $strparams);
                        $urlparams = array(
                            'user_id' => $this->student_id,
                            'course_id' => $this->course_id,
                            'selectedtab' => $this->selectedtab,
                            'tabitem' => $this->tabitem,
                            'showall' => true
                        );
                        $url = new moodle_url('/blocks/ilp/actions/view_main.php', $urlparams);
                        $url->set_anchor('showall');
                        $link = html_writer::link($url, $strshowall);
                        $loader = $OUTPUT->pix_icon('i/loading_small', 'Loading...', '', array('class' => 'loading'));
                        $pluginoutput .= $OUTPUT->container($link.$loader, 'showall');
                    }
                } else {

                    $pluginoutput .= get_string('nothingtodisplay');

                }
            }

            // load custom javascript
            $module = array(
                'name'      => 'ilp_dashboard_timeline_tab',
                'fullpath'  => '/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_timeline_tab.js',
                'requires'  => array(
                    'yui2-dom',
                    'yui2-event',
                    'yui2-connection',
                    'yui2-container',
                    'yui2-animation',
                    'base',
                    'io',
                    'io-form',
                    'node',
                    'json'
                )
            );

            // js arguments
            $jsarguments = array(
                'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
                'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
                'userid' => $USER->id,
                'selectedtab' => $this->selectedtab,
                'tabitem' => $this->tabitem
            );

            // initialise the js for the page
            $PAGE->requires->js_init_call('M.ilp_dashboard_timeline_tab.init', $jsarguments, true, $module);

        } else {
            $pluginoutput = get_string('studentnotfound','block_ilp');
        }

        return $OUTPUT->container($pluginoutput, '', 'ilp_dashboard_timeline_tab_wrapper');
    }

    function stateselector($report_id) {
        $stateselector = "";

        //find out if the report has state fields
        if ($this->dbc->has_plugin_field($report_id,'ilp_element_plugin_state')) {
            $states = $this->dbc->get_report_state_items($report_id,'ilp_element_plugin_state');
            $options = array();
            foreach($states as $s)    {
                $options[$this->plugin_id.':'.$report_id.':'.$s->id] = $s->name;
            }
            $params = array(
                'course_id' => $this->course_id,
                'user_id' => $this->student_id,
                'selectedtab' => $this->plugin_id
            );
            $url = new moodle_url('/', $params);
            $label = html_writer::label('Report State', 'tabitem');
            $hidden = html_writer::input_hidden_params($url);
            $select = html_writer::select($options,
                                          'tabitem',
                                          '',
                                          array($this->plugin_id.':'.$report_id => 'Any State'),
                                          array('id' => 'reportstateselect'));
            $buttonattrs = array('type' => 'submit', 'value' => 'Apply Filter', 'id' => 'stateselectorsubmit');
            $submit = html_writer::empty_tag('input', $buttonattrs);
            $formattrs = array('action' => $this->linkurl->out(false, array('selectedtab' => $this->plugin_id)));
            $form = html_writer::tag('form', $label.$hidden.$select.$submit, $formattrs);
            $stateselector = html_writer::tag('div', $form, array('class' => 'report_state'));
        }
        return $stateselector;
    }

    function render_addbuttons($addbuttons) {
        global $OUTPUT;
        $buttons = '';
        foreach ($addbuttons as $url => $string) {
            $link = html_writer::link($url, $string);
            $buttons .= $OUTPUT->container($link, 'add');
        }
        $buttons .= $OUTPUT->pix_icon('i/loading_small', 'Loading...', '', array('class' => 'loading'));
        return $OUTPUT->container($buttons, 'addbuttons');
    }

    function render_entry($entry) {
        global $OUTPUT, $USER, $PARSER, $PAGE;
        $hw = 'html_writer';
        $showprivate = $PARSER->optional_param('showprivate', 0, PARAM_INT);
        $is_private = false;
        $is_author = $USER->id == $entry->creator_id;

        $output = $OUTPUT->container('', 'clearfix');
        $leftcontent = '';
        if ($entry->report->has_private) {
            $privatename = $entry->report->has_private->id.'_field';
            if (isset($entry->data->$privatename)) {
                $is_private = $entry->data->$privatename == 'Yes';
            }
        }

        foreach ($entry->report->fields as $field) {
            if (!in_array($field->id, $entry->report->dontdisplay)) {
                // create the fieldname which will be used in to retrieve data from the object
                $fieldname = $field->id."_field";
                $label = $hw::tag('strong', $field->label);
                $content = (!empty($entry->data->$fieldname)) ? $entry->data->$fieldname : '';
                $leftcontent .= $hw::tag('p', $label.' '.$content);
            }
        }

        if ($is_private) {
            if ($showprivate != $entry->id) {
                $showall = $PARSER->optional_param('showall', 0, PARAM_INT);
                $urlparams = array(
                    'user_id' => $this->student_id,
                    'course_id' => $this->course_id,
                    'selectedtab' => $this->selectedtab,
                    'tabitem' => $this->tabitem,
                    'showall' => $showall,
                    'showprivate' => $entry->id
                );
                $showurl = new moodle_url('/blocks/ilp/actions/view_main.php', $urlparams);
                $showurl->set_anchor('entry'.$entry->id);
                $showlink = $hw::link($showurl, get_string('ilp_dashboard_timeline_tab_display', 'block_ilp'), array('class' => 'showprivate'));
                $privatemessage = $hw::tag('p', get_string('ilp_dashboard_timeline_tab_private', 'block_ilp').' '.$showlink);
                $leftcontent = $privatemessage.$OUTPUT->container($leftcontent, 'private');
            }
        }

        $commandcontent = '';
        if (!empty($entry->report->access['editreports']) && $is_author) {
            $editparams = array(
                'report_id' => $entry->report->id,
                'user_id' => $entry->data->user_id,
                'entry_id' => $entry->id,
                'course_id' => $this->course_id
            );
            $editurl = new moodle_url('/blocks/ilp/actions/edit_reportentry.php', $editparams);
            $stredit = get_string('edit');
            $editicon = $OUTPUT->pix_icon('/i/edit', $stredit);
            $commandcontent .= $hw::link($editurl, $stredit.$editicon).' | ';
        }

        if (!empty($entry->report->candelete) && $is_author) {
            $delparams = array(
                'report_id' => $entry->report->id,
                'user_id' => $entry->data->user_id,
                'entry_id' => $entry->id,
                'course_id' => $this->course_id,
                'tabitem' => $this->tabitem,
                'selectedtab' => $this->selectedtab
            );
            $delurl = new moodle_url('/blocks/ilp/actions/delete_reportentry.php', $delparams);
            $strdel = get_string('delete');
            $delicon = $OUTPUT->pix_icon('/t/delete', $strdel);
            $commandcontent .= $hw::link($delurl, $strdel.$delicon, array('class' => 'delete_reportentry'));
        }

        if ($reportstab = $this->dbc->get_tab_plugin_by_name('ilp_dashboard_reports_tab')) {
            if ($reportstab->status == 1) {
                $params = array(
                    'user_id' => $entry->data->user_id,
                    'course_id' => $this->course_id,
                    'selectedtab' => $reportstab->id,
                    'tabitem' => implode(':', array($reportstab->id, $entry->report->id))
                );
                $url = new moodle_url('/blocks/ilp/actions/view_main.php', $params);
                $type = $hw::link($url, $entry->report->name);
            }
        } else {
            $type = $entry->report->name;
        }
        $rightcontent = $hw::tag('p', get_string('type', 'block_ilp').' : '.$type);
        $rightcontent .= $hw::tag('p', get_string('addedby', 'block_ilp').' : '.$entry->data->creator);
        if (!empty($entry->report->has_courserelated)) {
            $rightcontent .= $hw::tag('p', get_string('course','block_ilp').' : '.$entry->data->coursename);
        }
        if (!empty($entry->report->has_deadline)) {
            $rightcontent .= $hw::tag('p', get_string('deadline','block_ilp').': '.userdate($entry->deadline, get_string('strftimedate')));
        }
        $rightcontent .= $hw::tag('p', get_string('date').' : '.$entry->data->modified);

        $commentscontent = '';

        $countcomments = (!empty($entry->comments)) ? count($entry->comments): "0";
        $strcomments = $OUTPUT->heading($countcomments.' '.get_string('comments', 'block_ilp'),
                                        3,
                                        array('commentheading'),
                                        'entry_'.$entry->id);

        if (!empty($entry->report->access['addcomment'])) {
            $addparams = array(
                'report_id' => $entry->report->id,
                'user_id' => $entry->data->user_id,
                'entry_id' => $entry->id,
                'selectedtab' => $this->selectedtab,
                'tabitem' => $this->tabitem,
                'course_id' => $this->course_id
            );
            $addurl = new moodle_url('/blocks/ilp/actions/edit_entrycomment.php', $addparams);
            $stradd = get_string('addcomment', 'block_ilp');
            $addlink = $hw::link($addurl, $stradd, array('class' => 'display_commentform', 'style' => 'float:right;margin: 0 0 0 200px;'));
        }

        $report = $leftreports = $OUTPUT->container($leftcontent, 'left-reports', 'entry'.$entry->id);
        $report .= $OUTPUT->container($commandcontent, 'commands');
        $report .= $OUTPUT->container($rightcontent, 'right-content');
        $report .= $OUTPUT->container('', 'clearfix');

        $commentscontent = $OUTPUT->container($strcomments.$OUTPUT->container($addlink, 'add'), 'view-comments');
        $report .= $OUTPUT->container($commentscontent, 'reports-comments');

        if (!empty($entry->comments) && !empty($entry->report->access['viewcomment'])) {
            $commentcontainerid = 'entry_'.$entry->id.'_container';
            $comments = '';
            foreach ($entry->comments as $c) {
                $comment_creator = $this->dbc->get_user_by_id($c->creator_id);
                $comment_content = $hw::tag('p', strip_tags(html_entity_decode($c->value)));
                $comment_info = get_string('creator','block_ilp').": ".fullname($comment_creator).' | ';
                $comment_info .= get_string('date').': '.userdate($c->timemodified, get_string('strftimedate')).' | ';

                if ($c->creator_id == $USER->id && !empty($entry->report->access['editcomment'])) {
                    $editparams = array(
                        'report_id' => $entry->report->id,
                        'user_id' => $entry->data->user_id,
                        'entry_id' => $entry->id,
                        'selectedtab' => $this->selectedtab,
                        'tabitem' => $this->tabitem,
                        'comment_id' => $c->id,
                        'course_id' => $this->course_id
                    );
                    $editurl = new moodle_url('/blocks/ilp/actions/edit_reportcomment.php', $editparams);
                    $stredit = get_string('edit');
                    $editicon = $OUTPUT->pix_icon('/i/edit', $stredit);
                    $comment_info .= $hw::link($editurl, $stredit.$editicon).' | ';
                }

                if ($c->creator_id == $USER->id && !empty($entry->report->access['deletecomment'])) {
                    $delparams = array(
                        'report_id' => $entry->report->id,
                        'user_id' => $entry->data->user_id,
                        'entry_id' => $entry->id,
                        'course_id' => $this->course_id,
                        'tabitem' => $this->tabitem,
                        'selectedtab' => $this->selectedtab,
                        'comment_id' => $c->id
                    );
                    $delurl = new moodle_url('/blocks/ilp/actions/delete_reportcomment.php', $delparams);
                    $strdel = get_string('delete');
                    $delicon = $OUTPUT->pix_icon('/t/delete', $strdel);
                    $comment_info .= $hw::link($delurl, $strdel.$delicon, array('class' => 'delete_reportcomment'));
                }

                $comments .= $OUTPUT->container($comment_content.$OUTPUT->container($comment_info, 'info'), 'comment');
            }
            $report .= $OUTPUT->container($comments, '', $commentcontainerid);
        }

        $output .= $OUTPUT->container($report, 'reports-container');
        return $output;
    }

    function collate_entries_by_date($reports, $showall = false) {
        $entries = array();
        foreach ($reports as $report) {
            $entries = $entries + $report->entries;
            unset($report->entries);
        }
        usort($entries, function($a, $b) {
            if ($a->timemodified > $b->timemodified) {
                return -1;
            } else if ($a->timemodified < $b->timemodified) {
                return 1;
            } else {
                return 0;
            }
        });
        if (!$showall && count($entries) > 20) {
            $entries = array_slice($entries, 0, 20);
        }
        foreach ($entries as $entry) {
            $entry->report = $reports[$entry->report_id];
        }
        return $entries;
    }


    /**
     * Adds the string values from the tab to the language file
     *
     * @param    array &$string the language strings array passed by reference so we
     * just need to simply add the plugins entries on to it
     */
     function language_strings(&$string) {
         $string['ilp_dashboard_timeline_tab'] = 'timeline tab';
         $string['ilp_dashboard_timeline_tab_name'] = 'Timeline';
         $string['ilp_dashboard_timeline_tab_reports'] = 'Report types to display on the timeline';
         $string['ilp_dashboard_timeline_tab_showall'] = 'Show all entries ({$a->count} more...)';
         $string['ilp_dashboard_timeline_tab_private'] = 'This entry is marked as private.';
         $string['ilp_dashboard_timeline_tab_display'] = 'Display';

        return $string;
    }


    function config_multiselect_element(&$mform,$elementname,$options,$label,$description,$defaultvalues = array()) {
        $this->config_select_element(&$mform,$elementname,$options,$label,$description);
        $select = $mform->getElement('s_'.$elementname);
        $select->setMultiple(true);
        $mform->setDefault('s_'.$elementname, $defaultvalues);
    }

    /**
       * Adds config settings for the plugin to the given mform
       * by default this allows config option allows a tab to be enabled or dispabled
       * override the function if you want more config options REMEMBER TO PUT
       *
       */
    function config_form(&$mform) {

        $reports = $this->dbc->get_reports(ILP_ENABLED);

        $options = array();

        if (!empty($reports)) {
            foreach ($reports as $r) {
                $options[$r->id] = $r->name;
            }
        }

        $selectedreports = unserialize(get_config('block_ilp', 'ilp_dashboard_timeline_tab_reports'));
        $this->config_multiselect_element($mform,
                                     'ilp_dashboard_timeline_tab_reports',
                                     $options,
                                     get_string('ilp_dashboard_timeline_tab_reports', 'block_ilp'),
                                     '',
                                     $selectedreports);
            //get the name of the current class
          $classname    =    get_class($this);

          $options = array(
            ILP_ENABLED => get_string('enabled','block_ilp'),
            ILP_DISABLED => get_string('disabled','block_ilp')
        );

          $this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);

    }

    function config_save($data) {
        $data->s_ilp_dashboard_timeline_tab_reports = serialize($data->s_ilp_dashboard_timeline_tab_reports);
        return parent::config_save($data);
    }


}
