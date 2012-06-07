<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

class ilp_element_plugin_contact_list extends ilp_element_plugin {

	public $tablename;
	public $data_entry_tablename;
	public $minimumlength;		//defined by the form creator to validate user input
	public $maximumlength;		//defined by the form creator to validate user input

	    /**
     * Constructor
     */
    function __construct() {
        $this->tablename = "block_ilp_plu_col";
    	$this->data_entry_tablename = "block_ilp_plu_col_ent";

    	parent::__construct();
    }


	/**
     * TODO comment this
     *
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
		if (!empty($reportfield)) {
			//set the reportfield_id var
			$this->reportfield_id	=	$reportfield_id;

			//get the record of the plugin used for the field
			$plugin		=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);

			$this->plugin_id	=	$reportfield->plugin_id;

			//get the form element record for the reportfield
			$pluginrecord	=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);

			if (!empty($pluginrecord)) {
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req				=	$reportfield->req;
				$this->maximumlength	=	$pluginrecord->maximumlength;
				$this->minimumlength	=	$pluginrecord->minimumlength;
				$this->position			=	$reportfield->position;
				return true;
			}
		}
		return false;
    }


	/**
     *
     */
    public function install() {
        global $CFG, $DB;

        // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_minlength = new $this->xmldb_field('minimumlength');
        $table_minlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_minlength);

        $table_maxlength = new $this->xmldb_field('maximumlength');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('textareaplugin_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);



        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }

	    // create the new table to store responses to fields
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_title = new $this->xmldb_field('value');
        $table_title->$set_attributes(XMLDB_TYPE_TEXT);
        $table->addField($table_title);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

       	$table_key = new $this->xmldb_key($this->tablename.'_foreign_key');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename ,'id');
        $table->addKey($table_key);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }


    }

    /**
     *
     */
    public function uninstall() {
        $table = new $this->xmldb_table( $this->tablename );
        drop_table($table);

        $table = new $this->xmldb_table( $this->data_entry_tablename );
        drop_table($table);
    }

     /**
     *
     */
    public function audit_type() {
        return get_string('ilp_element_plugin_contact_list_type','block_ilp');
    }

    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $prefix = get_class();
        $string[$prefix] = 'Textarea with Contact List';
        $string[$prefix.'_created'] = 'created';
        $string[$prefix.'_description'] = 'A textarea field which can be emailed to users';
        $string[$prefix.'_edited'] = 'edited';
        $string[$prefix.'_emailsubject'] = '{$a->student} [{$a->tutor}] - A {$a->report} has been {$a->action}';
        $string[$prefix.'_minimumlength'] = 'Minimum Length';
        $string[$prefix.'_maximumlength'] = 'Maximum Length';
        $string[$prefix.'_maxlengthrange'] = 'The maximum length field must have a value between 0 and 255';
        $string[$prefix.'_maxlessthanmin'] = 'The maximum length field must have a greater value than the minimum length';
        $string[$prefix.'_notifystaff'] = 'Notify Staff';
        $string[$prefix.'_notutorgroup'] = 'This student has no tutor group';
        $string[$prefix.'_preview'] = 'This is a preview, the contact list can only be displayed on a live report';
        $string[$prefix.'_stakeholdersearch'] = 'add more:';
        $string[$prefix.'_type'] = 'Contactlist';
        $string[$prefix.'_viewentry'] = 'Click this link to leave a reponse';

        return $string;
    }

   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id) {
    	return parent::delete_form_element($this->tablename, $reportfield_id);
    }

    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public function entry_form( &$mform ) {

    	//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";;
    	if (!empty($this->description)) {
    	$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description),ILP_STRIP_TAGS_DESCRIPTION));
    	$this->label = '';
    	}
    	//text field for element label
        $mform->addElement(
            'textarea',
            $fieldname,
            "$this->label",
            array('class' => 'form_input','rows'=> '20', 'cols'=>'65')
        );

        if (!empty($this->minimumlength)) $mform->addRule($fieldname, null, 'minlength', $this->minimumlength, 'client');
        if (!empty($this->maximumlength)) $mform->addRule($fieldname, null, 'maxlength', $this->maximumlength, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->setType($fieldname, PARAM_RAW);

        $this->add_contactlist($mform);
        $this->require_js();
    }

    public function entry_process_data($reportfield_id, $entry_id, $data) {
        return $this->entry_specific_process_data($reportfield_id, $entry_id, $data);
    }

    /**
    * handle user input
    **/
    public function entry_specific_process_data($reportfield_id, $entry_id, $data) {
        global $PARSER, $USER;

        //check to see if a entry record already exists for the reportfield in this plugin

        //create the fieldname
        $fieldname = $reportfield_id."_field";

        //get the plugin table record that has the reportfield_id
        $pluginrecord = $this->dbc->get_plugin_record($this->tablename, $reportfield_id);
        if (empty($pluginrecord)) {
            print_error('pluginrecordnotfound');
        }

        //get the _entry table record that has the pluginrecord id
        $pluginentry = $this->dbc->get_pluginentry($this->tablename, $entry_id, $reportfield_id);

        //if no record has been created create the entry record
        if (empty($pluginentry)) {
            $pluginentry = new stdClass();
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
            $pluginentry->entry_id = $entry_id;
            $pluginentry->value = $data->$fieldname;
            $pluginentry->parent_id = $pluginrecord->id;
            $result = $this->dbc->create_plugin_entry($this->data_entry_tablename, $pluginentry);
        } else {
            //update the current record
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
            $pluginentry->value =$data->$fieldname;
            $result = $this->dbc->update_plugin_entry($this->data_entry_tablename, $pluginentry);
        }

        if ($result) {
            $notifystaff = array();
            $hw = 'html_writer';
            foreach ($PARSER->optional_param('teachers', array(), ILP_PARAM_ARRAY) as $teacherid => $notify) {
                if ($notify) {
                    $notifystaff[] = $this->dbc->get_user_by_id($PARSER->clean_param($teacherid, PARAM_INT));
                }
            }

            if (!empty($notifystaff)) {
                if (empty($entry_id)) {
                    $action = get_string(get_class().'_created', 'block_ilp');
                } else {
                    $action = get_string(get_class().'_edited', 'block_ilp');
                }

                $linkparams = array(
                    'report_id' => $data->report_id,
                    'user_id' => $data->user_id,
                    'course_id' => $data->course_id,
                    'entry_id' => $entry_id
                );
                $linkurl = new moodle_url('/blocks/ilp/actions/edit_entrycomment.php', $linkparams);
                $linktext = get_string(get_class().'_viewentry', 'block_ilp');

                $subjectparams = (object)array(
                    'student' => fullname($this->dbc->get_user_by_id($data->user_id)),
                    'tutor' => fullname(current($this->dbc->get_student_tutors($data->user_id))),
                    'report' => $this->dbc->get_report_by_id($data->report_id)->name,
                    'action' => $action
                );
                $subject = get_string(get_class().'_emailsubject', 'block_ilp', $subjectparams);

                $entrytext = $data->$fieldname;
                $messagehtml = str_replace(PHP_EOL, '<br />', $entrytext)
                    .$hw::start_tag('p').get_string('addedby', 'block_ilp').': '
                    .$hw::tag('strong', fullname($USER)).' '
                    .get_string('date').': '
                    .$hw::tag('strong', userdate(time(), get_string('strftimedate'))).$hw::end_tag('p')
                    .$hw::link($linkurl, $linktext);
                $messagetext = $entrytext.PHP_EOL.PHP_EOL
                    .get_string('addedby', 'block_ilp')." ".fullname($USER)." ".get_string('date')
                    ." ".userdate(time(), get_string('strftimedate')).PHP_EOL.PHP_EOL
                    .$linktext.PHP_EOL.$linkurl->out(false);
                $from = new stdClass;
                foreach ($notifystaff as $staff) {
                    email_to_user($staff, 'Moodle PLP', $subject, $messagetext, $messagehtml);
                }
            }
            return true;
        }
    }

    protected function get_tutorgroup_context($userid) {
        global $DB;
        $select = 'SELECT c.id ';
        $from = 'FROM {course} AS c
            JOIN {context} AS con ON c.id = con.instanceid AND con.contextlevel = 50
            JOIN {role_assignments} AS ra ON con.id = ra.contextid AND ra.roleid = 5 ';

        $like = $DB->sql_like('c.shortname', '?');
        $where = 'WHERE '.$like.'
            AND ra.userid = ?';
        $params = array('______/___', $userid);
        $course = $DB->get_record_sql($select.$from.$where, $params);
        if (!$course) {
            return $course;
        }
        return get_context_instance(CONTEXT_COURSE, $course->id);
    }

    protected function get_tutors($userid) {
        global $DB,$CFG;
        if ($tgcontext = $this->get_tutorgroup_context($userid)) {
            $exceptions = get_users_by_capability(get_context_instance(CONTEXT_SYSTEM), 'gradereport/grader:view');
            $exception_array = array();
            foreach($exceptions as $exception) {
                $exception_array[] = $exception->id;
            }
            $tutors = get_users_by_capability($tgcontext, 'gradereport/grader:view', '', '', '', '', '', $exception_array);
            foreach ($tutors as $tutor) {
                $params = array('userid' => $tutor->id, 'contextid' => $tgcontext->id);
                if ($DB->record_exists('role_assignments', $params)) {
                    $tutor->istutor = true;
                } else {
                    $tutor->istutor = false;
                }
            }
            uasort($tutors, function($a, $b) {
                if ($a->istutor == $b->istutor) {
                    return 0;
                } else if ($a->istutor) {
                    return -1;
                } else {
                    return 1;
                }
            });
            return $tutors;
        } else {
            return false;
        }
    }

    protected function add_contactlist($mform) {
        global $DB, $USER;
        if (isset($mform->_elementIndex['user_id'])) {
            $userid = $mform->getElement('user_id')->getValue();
            $stakeholders = array();
            $courses = enrol_get_users_courses($userid, false, '*', 'visible DESC, sortorder ASC');
            foreach ($courses as $course) {
                if (!$DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'meta'))) {
                    $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
                    $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
                    $courseteachers = get_users_from_role_on_context($role , $coursecontext);
                    $laarole = $DB->get_record('role', array('shortname' => 'laa'));
                    $extras = get_users_from_role_on_context($laarole, get_context_instance(CONTEXT_SYSTEM));
                    $teachers_and_extras = array_merge($courseteachers, $extras);
                    foreach ($teachers_and_extras as $t_or_e) {
                        if ($t_or_e->userid != $USER->id) {
                            $teacher = $DB->get_record('user', array('id' => $t_or_e->userid));
                            $stakeholders[$teacher->id] = fullname($teacher);
                        }
                    }
                }
            }
            asort($stakeholders);
            $stakeholders_tutors = array();
            if ($tutors = $this->get_tutors($userid)) {
                foreach($tutors as $tutor) {
                    if ($tutor->id != $USER->id) {
                        $stakeholders_tutors[$tutor->id] = '<strong>'.fullname($tutor).'</strong>';
                    }
                }
            } else {
                $mform->addElement('static', '', '', '<strong>'.get_string('ilp_element_plugin_contact_list_notutorgroup', 'block_ilp').'</strong>');
            }
            $stakeholders = $stakeholders_tutors+$stakeholders;
            $checkboxes = array();
            $row = 0;
            $count = 0;
            foreach ($stakeholders as $id => $name) {
                $checkboxes[$row][] =& $mform->createElement('advcheckbox', $id, '', $name, array('group' => 1));
                $count++;
                if ($count > 2) {
                    $count = 0;
                    $row++;
                }
            }
            $label = get_string('ilp_element_plugin_contact_list_notifystaff', 'block_ilp');
            foreach($checkboxes as $row) {
                $mform->addGroup($row, 'teachers', $label);
                $label = '&nbsp;';
            }
            $mform->addElement('static', '', '', html_writer::empty_tag('div', array('id' => 'extrastakeholders')));
            $mform->addElement('text', 'stakeholdersearch', get_string('ilp_element_plugin_contact_list_stakeholdersearch', 'block_ilp'));
            $mform->addElement('html', html_writer::tag('div', '<ul />', array('id' => 'stakeholderresults')));
        } else {
            $mform->addElement('static', '', '', get_string('ilp_element_plugin_contact_list_preview', 'block_ilp'));
        }
    }

    protected function require_js() {
        global $PAGE;
        $jsmodule = array(
            'name'  =>  'ilp_element_plugin_contact_list',
            'fullpath'  =>  '/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_contact_list.js',
            'requires'  =>  array('base', 'node', 'io', 'json')
        );
        $PAGE->requires->js_init_call('M.ilp_element_plugin_contact_list.init', null, false, $jsmodule);
    }

}

