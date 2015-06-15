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
 * Event handlers for the moorsp plagiarism plugin.
 *
 * @package    plagiarism_moorsp
 * @copyright  2014 onwards Dan Marsden {@link http://danmarsden.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$handlers = array (

    /*
     * Event Handlers
     */
    'assessable_file_uploaded' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_file_uploaded',
        'schedule'         => 'instant'
    ),
    'assessable_files_done' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_files_done',
        'schedule'         => 'instant'
    ),
    'assessable_content_uploaded' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_content_uploaded',
        'schedule'         => 'instant'
    ),
    'assessable_content_done' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_content_done',
        'schedule'         => 'instant'
    ),
    'mod_created' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_mod_created',
        'schedule'         => 'instant'
    ),
    'mod_updated' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_mod_updated',
        'schedule'         => 'instant'
    ),
    'mod_deleted' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_mod_deleted',
        'schedule'         => 'instant'
    ),
    'assessable_submitted' => array (
        'handlerfile'      => '/plagiarism/moorsp/lib.php',
        'handlerfunction'  => 'moorsp_event_assessable_submitted',
        'schedule'         => 'instant'
    ),

);