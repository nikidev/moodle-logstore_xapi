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
 * Process events in queue.
 *
 * @package    logstore_xapi
 * @copyright  2015 Michael Aherne
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_xapi\task;

use tool_log\log\manager;
use logstore_xapi\log\store;
defined('MOODLE_INTERNAL') || die();

class emit_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskemit', 'logstore_xapi');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        $manager = get_log_manager();
        $store = new store($manager);
        $events = $DB->get_records('logstore_xapi_log');
        $store_return = $store->process_events($events);
        foreach(array_keys($store_return) as $event_id) {
            if ($store_return[$event_id] == 'success') {
                $DB->delete_records_list('logstore_xapi_log', 'id', array($event_id));
            }
        }

        mtrace("Sent learning records to LRS.");
    }
}
