<?php
echo $OUTPUT->header();
?>
<div class="ilp yui-skin-sam">
<?php

    $entry = $dbc->get_entry_by_id($entry_id);
    $entry->report = $report;
    $entry->data = new stdClass();

    $entry->report->fields = $dbc->get_report_fields($report->id);
    $entry->report->dontdisplay = array();
    $entry->report->access = array();
    $report->access['addcomment'] = false;
    $capability = $dbc->get_capability_by_name('block/ilp:addcomment');
    if (!empty($capability)) {
        $report->access['addcomment'] = $dbc->has_report_permission($report->id, $role_ids, $capability->id);
    }
    //find out if the current user has the edit comment capability for the report
    $report->access['viewcomment'] = false;
    $capability = $dbc->get_capability_by_name('block/ilp:viewcomment');
    if (!empty($capability)) {
        $report->access['viewcomment'] = $dbc->has_report_permission($report->id,$role_ids,$capability->id);
    }
    //does this report give user the ability to add comments
    $entry->comments = $dbc->get_entry_comments($entry->id);

    $entry->report->has_courserelated = (!$dbc->has_plugin_field($report->id,'ilp_element_plugin_course')) ? false : true;
    if (!empty($entry->report->has_courserelated)) {
        $courserelated = $dbc->has_plugin_field($entry->report->id, 'ilp_element_plugin_course');
        //the should not be anymore than one of these fields in a report
        foreach ($courserelated as $cr) {
            $entry->report->dontdisplay[] = $cr->id;
            $courserelatedfield_id = $cr->id;
        }
    }

    $entry->report->has_datedeadline = (bool)$dbc->has_plugin_field($entry->report->id, 'ilp_element_plugin_date_deadline');
    if ($entry->report->has_datedeadline) {
        $deadline = $dbc->has_plugin_field($entry->report->id,'ilp_element_plugin_date_deadline');
            //the should not be anymore than one of these fields in a report
        foreach ($deadline as $d) {
            $entry->report->dontdisplay[] = $d->id;
        }
    }

    //get the creator of the entry
    $creator = $dbc->get_user_by_id($entry->creator_id);

    //get comments for this entry
    $entry->comments = $dbc->get_entry_comments($entry->id);

    //
    $entry->data->creator = (!empty($creator)) ? fullname($creator) : get_string('notfound','block_ilp');
    $entry->data->created = userdate($entry->timecreated);
    $entry->data->modified = userdate($entry->timemodified);
    $entry->data->user_id = $entry->user_id;
    $entry->data->entry_id = $entry->id;

    if ($entry->report->has_courserelated) {
        $coursename = false;
        $crfield = $dbc->get_report_coursefield($entry->id, $courserelatedfield_id);
        if (empty($crfield) || empty($crfield->value)) {
            $coursename = get_string('allcourses','block_ilp');
        } else if ($crfield->value == '-1') {
            $coursename = get_string('personal','block_ilp');
        } else {
            $crc = $dbc->get_course_by_id($crfield->value);
            if (!empty($crc)) $coursename = $crc->shortname;
        }
        $entry->data->coursename = (!empty($coursename)) ? $coursename : '';
    }

    foreach ($entry->report->fields as $field) {

        //get the plugin record that for the plugin
        $pluginrecord = $dbc->get_plugin_by_id($field->plugin_id);

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

    require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_timeline_tab.php');
    $tab = new ilp_dashboard_timeline_tab();
    $html = $tab->render_entry($entry);
    echo $html;

?>
</div>
<?php
echo $OUTPUT->footer();
?>
