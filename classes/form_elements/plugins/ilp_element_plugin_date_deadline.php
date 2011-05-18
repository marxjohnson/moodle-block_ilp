<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

class ilp_element_plugin_date_deadline extends ilp_element_plugin {

	public $datetense;	//offers the form creator 'past', 'present' and 'future' options to control validation of the user input	
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	$this->tablename = "block_ilp_plu_ddl";
    	
    	parent::__construct();
    }
	
	
	/**
     * TODO comment this
     *
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$plugin		=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$this->plugin_id	=	$plugin_id;
			$pluginrecord	=	$this->dbc->get_form_element_data($this->tablename,$reportfield->id);
			if (!empty($plugin_record)) {
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->required			=	$reportfield->req;
				$this->datetense		=	$reportfield->datetense;
				$this->position			=	$reportfield->position;
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
        $table = new $this->xmldb_table('block_ilp_plu_ddl');
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_datatense = new $this->xmldb_field('datatense');
        $table_datatense->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_minlength);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('date_unique_reportfield');
        $table_key->setAttributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);
        

    /// Launch add key unique_position_report
        //$result = $result && add_key($table, $key);

    /// Launch add key unique_position_report
        $result = $result && add_key($table, $key);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
        
	    // create the new table to store responses to fields
        $table = new $this->xmldb_table('block_ilp_plu_dat_ent');
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_title = new $this->xmldb_field('value');
        $table_title->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_title);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_maxlength = new $this->xmldb_field('textfield_id');
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
        
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
        
    }

    /**
     *
     */
    public function uninstall() {
        $table = new $this->xmldb_table('block_ilp_plu_dat_ent');
        drop_table($table);
        
        $table = new $this->xmldb_table('block_ilp_plu_dat');
        drop_table($table);
    }
	
     /**
     *
     */
    public function audit_type() {
        return 'Textarea Field';
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_date'] 		= 'Date selector';
        $string['ilp_element_plugin_date_type'] 	= 'date selector';
        $string['ilp_element_plugin_date_description'] 	= 'A date entry element';
        $string['ilp_element_plugin_datetense'] 	= 'Date tense';
        $string['ilp_element_plugin_date_past'] 	= 'past';
        $string['ilp_element_plugin_date_present'] 	= 'present';
        $string['ilp_element_plugin_date_future'] 	= 'future';
        $string['ilp_element_plugin_date_any'] 		= 'none of the above, or a mixture';
        
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
    public	function entry_form($mform) {
    	

    	//text field for element label
        $mform->addElement(
            'date_selector',
            "$this->reportfield_id",
            "$this->label",
            array('class' => 'form_input')
        );
        
        if (!empty($this->req)) $mform->addRule("$this->reportfield_id", null, 'required', null, 'client');
	//@todo decide correct PARAM type for date element
        $mform->setType($this->reportfield_id, PARAM_RAW);
    	
        return $mfrom;
    	
    	
    }
}
