<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Functions used by the moorsp plagiarism plugin.
 *
 * @package    plagiarism_moorsp
 * @copyright  2014 onwards Dan Marsden {@link http://danmarsden.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//get global class
require_once($CFG->dirroot.'/plagiarism/lib.php');

define('PLAGIARISM_MOORSP_DRAFTSUBMIT_IMMEDIATE', 0);
define('PLAGIARISM_MOORSP_DRAFTSUBMIT_FINAL', 1);

/**
 * Class plagiarism_plugin_moorsp
 */
class plagiarism_plugin_moorsp extends plagiarism_plugin {
    /**
     * This function should be used to initialise settings and check if Moorsp is enabled.
     *
     * @return mixed - false if not enabled, or returns an array of relevant settings.
     */
    public function get_settings() {
        static $plagiarismsettings;
        if (!empty($plagiarismsettings) || $plagiarismsettings === false) {
            return $plagiarismsettings;
        }
        $plagiarismsettings = $plagiarismsettings = array_merge((array)get_config('plagiarism'),
            (array)get_config('plagiarism_moorsp'));
        // Check if enabled.
        if (isset($plagiarismsettings['moorsp_use']) && $plagiarismsettings['moorsp_use']) {
            return $plagiarismsettings;
        } else {
            return false;
        }
    }

    /**
     * Check whether Moorsp needs to be used in a particular instance.
     *
     * @param $cmid int Course module id
     * @return boolean whether Moorsp is enabled for the given cmid
     */
    public function is_moorsp_used($cmid) {
        global $DB;
        $useforcm = false;
        $cmenabled = false;
        $plagiarismvalues = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $cmid), '', 'name, value');
        if ($plagiarismvalues['use_moorsp']) {
            // Moorsp is used for this cm
            $useforcm = true;
        }

        // Check if the module associated with this event still exists.
        if ($DB->record_exists('course_modules', array('id' => $cmid))) {
            $cmenabled = true;
        }
        return ($useforcm && $cmenabled);
    }
     /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     * 
     */
    public function get_links($linkarray) {
        //$userid, $file, $cmid, $course, $module
        global $OUTPUT;
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        if (!empty($linkarray['file'])) {
            $file = new stdClass();
            $file->filename = $linkarray['file']->get_filename();
            $file->identifier = $linkarray['file']->get_contenthash();
            $file->timestamp = time();
            $file->filepath = $linkarray['file']->get_filepath();
        } else if (!empty($linkarray['content'])) {
            $file = new stdClass();
            $contenthash = md5($linkarray['content']);
            $file->filename = 'content_' . $contenthash;
            $file->identifier = $contenthash;
            $file->timestamp = time();
        }
        $results = $this->get_file_results($cmid, $userid, $file);
        $output = '';
        //add link/information about this file to $output
        if ($results['analyzed'] == 0) {
            $output .= '<span class="plagiarismreport">'.
                '<img src="'.$OUTPUT->pix_url('processing', 'plagiarism_moorsp') .
                '" alt="'.get_string('pending', 'plagiarism_moorsp').'" '.
                '" title="'.get_string('pending', 'plagiarism_moorsp').'" />'.
                '</span>';
        } else {
            if ($results['score'] == 1) {
                $output .= '<span class="plagiarismreport">'.
                    '<img src="'.$OUTPUT->pix_url('warning', 'plagiarism_moorsp') .
                    '" alt="'.get_string('plagiarised', 'plagiarism_moorsp').'" '.
                    '" title="'.get_string('plagiarised', 'plagiarism_moorsp').'" />'.
                    '</span>';
            } else {
                $output .= '<span class="plagiarismreport">'.
                    '<img src="'.$OUTPUT->pix_url('ok', 'plagiarism_moorsp') .
                    '" alt="'.get_string('not_plagiarised', 'plagiarism_moorsp').'" '.
                    '" title="'.get_string('not_plagiarised', 'plagiarism_moorsp').'" />'.
                    '</span>';
            }

        }
        return $output;
    }

    /**
     * hook to allow plagiarism specific information to be returned unformatted
     * @param int $cmid
     * @param int $userid
     * @param $file file object
     * @return array containing at least:
     *   - 'analyzed' - whether the file has been successfully analyzed
     *   - 'score' - similarity score - ('' if not known)
     *   - 'reporturl' - url of originality report - '' if unavailable
     */
    public function get_file_results($cmid, $userid, $file) {
        global $DB;
        $plagiarismsettings = $this->get_settings();
        if (!$plagiarismsettings) {
            return false;
        }
        if (!$this->is_moorsp_used($cmid)) {
            return false;
        }
        $filehash = $file->identifier;
        $results = array('error' => '', 'score' => '',
            'analyzed' => 0, 'reporturl' => ''
        );

        $modulecontext = context_module::instance($cmid);
        // If the user has permission to see result of all items in this course module.
        $viewscore = $viewreport = has_capability('plagiarism/moorsp:viewreport', $modulecontext);
        // If the file has already been analyzed, return those results
        $storedfile = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE cm = ? AND userid = ? AND " .
            "identifier = ?",
            array($cmid, $userid, $filehash));
        if (empty($storedfile)) {
            return false;
        }
        if ($storedfile->statuscode == 'analyzed') {
            $results['analyzed'] = 1;
            $results['score'] = $storedfile->similarity;
            $results['error'] = $storedfile->errorresponse;
        } else {
            $plagiarismfile = $DB->get_record_sql(
                "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE userid != ? AND identifier = ?", array($userid, $filehash));
            $updatefile = new stdClass();
            $updatefile->id = $storedfile->id;
            $updatefile->statuscode = 'analyzed';
            $updatefile->attempt = $storedfile->attempt + 1;

            $results['analyzed'] = 1;
            if (!empty($plagiarismfile)) {
                // File is plagiarised based on file content hash
                $updatefile->similarity = 1;
                $results['score'] = 1;
            } else {
                // File is not plagiarised.
                $updatefile->similarity = 0;
                $results['score'] = 0;
            }
            $DB->update_record('plagiarism_moorsp_files', $updatefile);
        }
        if (!$viewscore && !$viewreport) {
            // User is not permitted to see any details.
            return false;
        }
        return $results;
    }

    /**
     * Handles text submissions, storing it in moorsp_files table.
     * @param $cmid Course module ID
     * @param $userid User ID
     * @param $content Content of the text submission
     * @return bool Whether the store function was successful
     */
    public function handle_onlinetext($cmid, $userid, $content) {
        $filehash = md5($content);
        $file = new stdClass();
        $file->identifier = $filehash;
        $file->filename = 'content_' . $filehash;
        return $this->update_plagiarism_file($cmid, $userid, $file);

    }
    /**
     * Updates a file record to be processed by Moorsp.
     *
     * @param int $cmid course module id
     * @param int $userid  user id
     * @param mixed $file the file from file storage
     * @return bool Whether the file was successfully stored
     */
    public function update_plagiarism_file($cmid, $userid, $file) {
        global $DB;

        $filehash = (!empty($file->identifier)) ? $file->identifier : $file->get_contenthash();
        // Now update or insert record into moorsp_files.
        $plagiarismfile = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE cm = ? AND userid = ? AND " .
            "identifier = ?",
            array($cmid, $userid, $filehash));
        if (!empty($plagiarismfile)) {
            // File is already there, return true
            return true;
        } else {
            $plagiarismfile = new stdClass();
            $plagiarismfile->cm = $cmid;
            $plagiarismfile->userid = $userid;
            $plagiarismfile->identifier = $filehash;
            $plagiarismfile->filename = (!empty($file->filename)) ? $file->filename : $file->get_filename();
            $plagiarismfile->statuscode = 'pending';
            $plagiarismfile->attempt = 0;
            $plagiarismfile->timesubmitted = time();
            if (!$pid = $DB->insert_record('plagiarism_moorsp_files', $plagiarismfile)) {
                debugging("insert into moorsp_files failed");
            }
            return isset($pid);
        }
    }
    /**
     * Hook to save plagiarism specific settings on a module settings page.
     * @param $data data from an mform submission.
     */
    public function save_form_elements($data) {
        global $DB;
        if (!$this->get_settings()) {
            return;
        }
        if (isset($data->use_moorsp)) {
            // Array of possible plagiarism config options.
            $plagiarismelements = $this->config_options();
            // First get existing values.
            $existingelements = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $data->coursemodule),
                '', 'name, id');
            foreach ($plagiarismelements as $element) {
                $newelement = new stdClass();
                $newelement->cm = $data->coursemodule;
                $newelement->name = $element;
                $newelement->value = (isset($data->$element) ? $data->$element : 0);
                if (isset($existingelements[$element])) {
                    $newelement->id = $existingelements[$element];
                    $DB->update_record('plagiarism_moorsp_config', $newelement);
                } else {
                    $DB->insert_record('plagiarism_moorsp_config', $newelement);
                }

            }
        }
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB;
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $studentshowoptions = array(0 => get_string("never"), 1 => get_string("always"));
        $moorspdraftoptions = array(
            PLAGIARISM_MOORSP_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_moorsp"),
            PLAGIARISM_MOORSP_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_moorsp")
        );
        $plagiarismsettings = array_merge((array)get_config('plagiarism'), (array)get_config('plagiarism_moorsp'));
        if(!$plagiarismsettings) {
            return;
        }
        $cmid = optional_param('update', 0, PARAM_INT); // Get cm as $this->_cm is not available here.
        if (!empty($modulename)) {
            $modname = 'moorsp_enable_' . $modulename;
            if (empty($plagiarismsettings[$modname])) {
                return;             // Return if moorsp is not enabled for the module.
            }
        }
        if (!empty($cmid)) {
            $plagiarismvalues = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $cmid), '', 'name, value');
        }
        // Get Defaults - cmid(0) is the default list.
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => 0), '', 'name, value');
        $plagiarismelements = $this->config_options();

        if (has_capability('plagiarism/moorsp:enable', $context)) {
            $mform->addElement('header', 'plagiarismdesc', get_string('pluginname', 'plagiarism_moorsp'));
            $mform->addElement('select', 'use_moorsp', get_string('usemoorsp', 'plagiarism_moorsp'), $ynoptions);
            $mform->addElement('select', 'moorsp_show_student_plagiarism_info',
                get_string("moorsp_show_student_plagiarism_info", "plagiarism_moorsp"), $studentshowoptions);
            if ($mform->elementExists('submissiondrafts')) {
                $mform->addElement('select', 'moorsp_draft_submit',
                    get_string("moorsp_draft_submit", "plagiarism_moorsp"), $moorspdraftoptions);
            }
            if ($mform->elementExists('moorsp_draft_submit')) {
                if ($mform->elementExists('submissiondrafts')) {
                    $mform->disabledIf('moorsp_draft_submit', 'submissiondrafts', 'eq', 0);
                }
            }
            // Disable all plagiarism elements if use_plagiarism eg 0.
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_moorsp') { // Ignore this var.
                    $mform->disabledIf($element, 'use_moorsp', 'eq', 0);
                }
            }
        } else { // Add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $mform->addElement('hidden', $element);
                $mform->setType('use_moorsp', PARAM_INT);
                $mform->setType('moorsp_show_student_plagiarism_info', PARAM_INT);
                $mform->setType('moorsp_draft_submit', PARAM_INT);
            }
        }
        // Now set defaults.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $mform->setDefault($element, $plagiarismvalues[$element]);
            } else if (isset($plagiarismdefaults[$element])) {
                $mform->setDefault($element, $plagiarismdefaults[$element]);
            }
        }


    }

    /**
     * hook to allow a disclosure to be printed notifying users what will happen with their submission
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $OUTPUT, $DB;
        $plagiarismsettings = $this->get_settings();
        $plagiarismvalues = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $cmid), '', 'name, value');
        if (empty($plagiarismvalues['use_moorsp'])) {
            // Moorsp not in use for this cm - return.
            return true;
        }
        $outputhtml = '';
        $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $outputhtml .= format_text($plagiarismsettings['moorsp_student_disclosure'], FORMAT_MOODLE, $formatoptions);
        $outputhtml .= $OUTPUT->box_end();
        return $outputhtml;
    }

    /**
     * hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
        //called at top of submissions/grading pages - allows printing of admin style links or updating status
    }

    /**
     * called by admin/cron.php 
     *
     */
    public function cron() {
        //do any scheduled task stuff
    }
    /**
     * Function which returns an array of all the module instance settings.
     *
     * @return array
     *
     */
    public function config_options()
    {
        return array('use_moorsp', 'moorsp_show_student_plagiarism_info',
            'moorsp_draft_submit');
    }


}
/**
 * Handler for all plagiarism events, observers will route here.
 * @param $eventdata array Event data
 * @throws coding_exception
 */
function moorsp_handle_event($eventdata) {
    global $DB, $CFG;
    $moorsp = new plagiarism_plugin_moorsp();
    $plagiarismsettings = $moorsp->get_settings();
    if (!$plagiarismsettings) {
        return;
    }
    if (!$moorsp->is_moorsp_used($eventdata['contextinstanceid'])) {
        return;
    }
    $cmid = $eventdata['contextinstanceid'];
    // Normal scenario - this is an upload event with one or more attached files
    if (!empty($eventdata['other']['pathnamehashes'])) {
        foreach ($eventdata['other']['pathnamehashes'] as $hash) {
            $fs = get_file_storage();
            $efile = $fs->get_file_by_hash($hash);

            if (empty($efile)) {
                mtrace("nofilefound!");
                continue;
            } else if ($efile->get_filename() === '.') {
                // This is a directory - nothing to do.
                continue;
            }

            // Check if assign group submission is being used.
            if ($eventdata['component'] == 'assignsubmission_file'
                || $eventdata['component'] == 'assignsubmission_onlinetext') {
                require_once("$CFG->dirroot/mod/assign/locallib.php");
                $modulecontext = context_module::instance($cmid);
                $assign = new assign($modulecontext, false, false);
                if (!empty($assign->get_instance()->teamsubmission)) {
                    $mygroups = groups_get_user_groups($assign->get_course()->id, $eventdata['userid']);
                    if (count($mygroups) == 1) {
                        $groupid = reset($mygroups)[0];
                        // Only users with single groups are supported - otherwise just use the normal userid on this record.
                        // Get all users from this group.
                        $userids = array();
                        $users = groups_get_members($groupid, 'u.id');
                        foreach ($users as $u) {
                            $userids[] = $u->id;
                        }
                        // Find the earliest plagiarism record for this cm with any of these users.
                        $sql = 'cm = ? AND userid IN (' . implode(',', $userids) . ')';
                        $previousfiles = $DB->get_records_select('plagiarism_moorsp_files', $sql, array($cmid), 'id');
                        $sanitycheckusers = 10; // Search through this number of users to find a valid previous submission.
                        $i = 0;
                        foreach ($previousfiles as $pf) {
                            if ($pf->userid == $eventdata['userid']) {
                                break; // The submission comes from this user so break.
                            }
                            // Sanity Check to make sure the user isn't in multiple groups.
                            $pfgroups = groups_get_user_groups($assign->get_course()->id, $pf->userid);
                            if (count($pfgroups) == 1) {
                                // This user made the first valid submission so use their id when sending the file.
                                $eventdata['userid'] = $pf->userid;
                                break;
                            }
                            if ($i >= $sanitycheckusers) {
                                // don't cause a massive loop here and break at a sensible limit.
                                break;
                            }
                            $i++;
                        }
                    }
                }
            }
            $moorsp->update_plagiarism_file($cmid, $eventdata['userid'], $efile);
        }
    }
    if (!empty($eventdata['other']['content'])) {
        // Online text submission scenario
        $moorsp->handle_onlinetext($cmid, $eventdata['userid'], $eventdata['other']['content']);
    }
}


