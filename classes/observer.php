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
* Event observers used in Moorsp Plagiarism plugin.
*
* @copyright  2015 Ramindu Deshapriya <rasade88@gmail.com>
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

class plagiarism_moorsp_observer {
    /**
     * Observer function to handle the assessable_uploaded event in mod_assign.
     * @param \assignsubmission_file\event\assessable_uploaded $event
     */
    public static function assignsubmission_file_uploaded(
        \assignsubmission_file\event\assessable_uploaded $event) {
        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');
        moorsp_handle_event($event->get_data());
    }
    /**
     * Observer function to handle the assessable_uploaded event in mod_forum.
     * @param \mod_forum\event\assessable_uploaded $event
     */
    public static function forum_file_uploaded(
        \mod_forum\event\assessable_uploaded $event) {
        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');
        moorsp_handle_event($event->get_data());
    }
    /**
     * Observer function to handle the assessable_uploaded event in mod_workshop.
     * @param \mod_workshop\event\assessable_uploaded $event
     */
    public static function workshop_file_uploaded(
        \mod_workshop\event\assessable_uploaded $event) {
        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');
        moorsp_handle_event($event->get_data());
    }
    /**
     * Observer function to handle the assessable_uploaded event in mod_assign onlinetext.
     * @param \assignsubmission_onlinetext\event\assessable_uploaded $event
     */
    public static function assignsubmission_onlinetext_uploaded(
        \assignsubmission_onlinetext\event\assessable_uploaded $event) {
        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');
        moorsp_handle_event($event->get_data());
    }
    /**
     * Observer function to handle the assessable_submitted event in mod_assign.
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assignsubmission_submitted(
        \mod_assign\event\assessable_submitted $event) {
        global $CFG;
        require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');
        moorsp_handle_event($event->get_data());
    }
}