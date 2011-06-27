<?php
/**
 * Ajax file for view_students
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE, $OUTPUT;

//include the default class
require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

//get the id of the course that is currently being used
$course_id 	= $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the tutor flag
$tutor		=	$PARSER->optional_param('tutor', NULL, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();




// set up the flexible table for displaying the portfolios
$flextable = new ilp_ajax_table('student_list');



$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/view_students.php?course_id={$course_id}&tutor={$tutor}");
$flextable->define_ajaxurl($CFG->wwwroot."/blocks/ilp/actions/view_students.ajax.php?course_id={$course_id}&tutor={$tutor}");




// set the basic details to dispaly in the table
$headers = array(
    get_string('name', 'block_ilp'),
    get_string('status', 'block_ilp')
);

$columns = array(
  	'user',
	'u_status'
);


//we need to check if the mis plugin has been setup if it has we will get the attendance and punchuality figures

//include the attendance 
$misclassfile	=	$CFG->docroot."/blocks/ilp/classes/mis.class.php";

//we will assume the mis data is unavailable until proven otherwise
$misavailable = false;

//only proceed if a mis file has been created
if (file_exists($misclassfile)) {
	
	//create an instance of the MIS class
	$misclass	=	new mis();
	
	$punch_method1 = array($misclass, 'get_total_punchuality');
	$punch_method2 = array($misclass, 'get_student_punchuality');
	$attend_method1 = array($misclass, 'get_total_attendance');
	$attend_method2 = array($misclass, 'get_student_attendance');
        
	//check whether the necessary functions have been defined
	 if (is_callable($attend_method1,true) && is_callable($attend_method2,true)) {
	 	$headers[] = get_string('attendance','block_ilp');
	 	$columns[] = 'u_attendcance';
	 	$misattendavailable = true;
	 }	
	 
	 //check whether the necessary functions have been defined
	 if (is_callable($punch_method1,true) && is_callable($punch_method2,true)) {
	 	$headers[] = get_string('punctulaity','block_ilp');
		$columns[] = 'u_punctuality';
		$mispunchavailable = true;
	 }
	 
}

//get all enabled reports in this ilp
$reports		=	$dbc->get_reports(ILP_ENABLED);
			
//we are going to create headers and columns for all enabled reports 
foreach ($reports as $r) {
	$headers[]	=	$r->name;
	$columns[]	=	$r->id;
}

$headers[]	=	get_string('lastupdated','block_ilp');
$columns[]	=	'lastupdated';

$headers[]	=	'';
$columns[]	=	'view';

//define the columns and the headers in the flextable
$flextable->define_columns($columns);
$flextable->define_headers($headers);

$flextable->initialbars(true);

$flextable->set_attribute('summary', get_string('studentslist', 'block_ilp'));
$flextable->set_attribute('cellspacing', '0');
$flextable->set_attribute('class', 'generaltable fit');

$flextable->setup();

if (!empty($course_id)) {
    $users	=	$dbc->get_course_users($course_id);
} else {
	$users	=	$dbc->get_user_tutees($USER->id);
}


$studentslist	=	$dbc->get_students_matrix($flextable,$users);

if(!empty($studentslist)) {
	
	
    foreach($studentslist as $stu) {
    	$data	=	array();
    	
    	$data['user']	=	$OUTPUT->user_picture($stu,array('return'=>true,'size'=>50));
    	$data['u_status'] =   $stu->u_status; 

    	if (!empty($misattendavailable)) {
    		$total 		=	$misclass->get_total_attendance();
    		$actual 	=	$misclass->get_student_attendance();
    		//we only want to try to find the percentage if we can get the total possible
    		// attendance else set it to 0;
    		$data['u_attendcance'] =	(!empty($total)) ? $actual / $total	* 100 : 0 ;
    	}
    	
    	
    	if (!empty($misattendavailable)) {
    		$total 		=	$misclass->get_total_attendance();
    		$actual 	=	$misclass->get_student_attendance();
    		//we only want to try to find the percentage if we can get the total possible
    		// attendance else set it to 0;
    		$data['u_attendcance'] =	(!empty($total)) ? $actual / $total	* 100 : 0 ;
    	}
	

      	foreach ($reports as $r) {

      		//get the number of this report that have been created
      		$reportnumber	=	$dbc->count_report_entries($r->id,$stu->id);
      		
      		$reporttext	=	"{$reportnumber} ".$r->name;
      		
      		//check if the report has a state field
      		if ($dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {
      			
      			//count the number of entries with a pass state
      			$reportentered = $dbc->count_report_entries_with_state($r->id,$stu->id,ILP_PASSFAIL_PASS);
      			$reporttext	= $reportentered. "/".$reportentered." ".get_string('achieved','block_ilp');	
      		}
      		
			$data[$r->id]	=	$reporttext;	
		}
    	
    	
		
		$data['view']	=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$stu->id}' >".get_string('viewplp','block_ilp')."</a>";
    	 $flextable->add_data_keyed($data);
    }
}    
 
$flextable->print_html();