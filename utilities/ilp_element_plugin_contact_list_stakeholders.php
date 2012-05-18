<?php

define('AJAX_SCRIPT', true);
require_once('../../../config.php');

require_login($SITE);

$search = required_param('search', PARAM_TEXT);
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$staffrole = $DB->get_record('role', array('shortname' => 'staff'));

$staffenrolments = get_users_from_role_on_context($staffrole, $systemcontext);

$stakeholders = array();
foreach($staffenrolments as $staffenrolment) {
    $staffuser = $DB->get_record('user', array('id' => $staffenrolment->userid));
    if (strpos(strtolower(fullname($staffuser)), strtolower($search)) !== false) {
        $stakeholders[$staffuser->id] = fullname($staffuser);
    }
}

$output = (object)array('stakeholders' => $stakeholders);
echo json_encode($output);
?>
