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
    static public function get_settings() {
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
     * @param array eventdata for the plagiarism event
     * @return boolean whether Moorsp needs to be used to handle the event
     */
    public function is_moorsp_used($eventdata) {
        global $DB;
        $useforcm = false;
        $cmenabled = false;
        $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
        $plagiarismvalues = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $cmid), '', 'name, value');
        if ($plagiarismvalues['use_moorsp']) {
            // Moorsp is used for this cm
            $useforcm = true;
        }

        // Check if the module associated with this event still exists.
        if ($DB->record_exists('course_modules', array('id' => $eventdata->cmid))) {
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
        $cmid = $linkarray['cmid'];
        $userid = $linkarray['userid'];
        $file = $linkarray['file'];
        $output = '';
        //add link/information about this file to $output
         
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
        return array('analyzed' => '', 'score' => '', 'reporturl' => '');
    }
    /**
     * Updates a file record to be processed by Moorsp.
     *
     * @param int $cmid - course module id
     * @param int $userid - user id
     * @param mixed $file the file from file storage
     * @return int - id of moorsp_files record
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
            return $plagiarismfile;
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
            $plagiarismfile->id = $pid;
            return $plagiarismfile;
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
        global $DB, $PAGE, $CFG;
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $tiioptions = array(0 => get_string("never"), 1 => get_string("always"));
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
                get_string("moorsp_show_student_plagiarism_info", "plagiarism_moorsp"), $tiioptions);
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
    public function config_options() {
        return array('use_moorsp', 'moorsp_show_student_plagiarism_info',
            'moorsp_draft_submit');
    }
}

function moorsp_event_file_uploaded($eventdata) {
    global $DB, $CFG;
    $result = true;
    $moorsp = new plagiarism_plugin_moorsp();
    $moorspfiles = array();
    $plagiarismsettings = $moorsp->get_settings();
    if (!$plagiarismsettings) {
        return true;
    }
    if (!$moorsp->is_moorsp_used($eventdata)) {
        return true;
    }
    $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
    if (isset($plagiarismvalues['moorsp_draft_submit']) &&
        $plagiarismvalues['moorsp_draft_submit'] == PLAGIARISM_MOORSP_DRAFTSUBMIT_FINAL) {
        require_once("$CFG->dirroot/mod/assign/locallib.php");
        require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");

        $modulecontext = context_module::instance($eventdata->cmid);
        $fs = get_file_storage();
        if ($files = $fs->get_area_files   ($modulecontext->id, 'assignsubmission_file',
            ASSIGNSUBMISSION_FILE_FILEAREA, $eventdata->itemid, "id", false)) {
            foreach ($files as $file) {
                $moorspfiles[] = $moorsp->update_plagiarism_file($cmid, $eventdata->userid, $file);
            }
        }
    }

    return $result;
}
function moorsp_event_files_done($eventdata) {
    global $DB, $CFG;
    $result = true;
    $moorsp = new plagiarism_plugin_moorsp();
    $moorspfiles = array();
    $plagiarismsettings = $moorsp->get_settings();
    if (!$plagiarismsettings) {
        return true;
    }
    if (!$moorsp->is_moorsp_used($eventdata)) {
        return true;
    }
    $cmid = (!empty($eventdata->cm->id)) ? $eventdata->cm->id : $eventdata->cmid;
    if (isset($plagiarismvalues['moorsp_draft_submit']) &&
        $plagiarismvalues['moorsp_draft_submit'] == PLAGIARISM_MOORSP_DRAFTSUBMIT_FINAL) {
        require_once("$CFG->dirroot/mod/assign/locallib.php");
        require_once("$CFG->dirroot/mod/assign/submission/file/locallib.php");

        $modulecontext = context_module::instance($eventdata->cmid);
        $fs = get_file_storage();
        if ($files = $fs->get_area_files   ($modulecontext->id, 'assignsubmission_file',
            ASSIGNSUBMISSION_FILE_FILEAREA, $eventdata->itemid, "id", false)) {
            foreach ($files as $file) {
                $moorspfiles[] = $moorsp->update_plagiarism_file($cmid, $eventdata->userid, $file);
            }
        }
    }
    return $result;
}

function moorsp_event_mod_created($eventdata) {
    $result = true;
        //a new module has been created - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function moorsp_event_mod_updated($eventdata) {
    $result = true;
        //a module has been updated - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function moorsp_event_mod_deleted($eventdata) {
    $result = true;
        //a module has been deleted - this is a generic event that is called for all module types
        //make sure you check the type of module before handling if needed.

    return $result;
}

function moorsp_event_content_uploaded($eventdata) {
    $result = true;

    return $result;
}

function moorsp_event_content_done($eventdata) {
    $result = true;

    return $result;
}

function moorsp_event_assessable_submitted($eventdata) {
    $result = true;

    return $result;

}


