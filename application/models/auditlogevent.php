<?php
/**
 * Audit log event
 *
 * @package    PluginDir
 * @subpackage models
 * @author     l.m.orchard <lorchard@mozilla.com>
 */

/**
 * Audit log event model class
 */
class AuditLogEvent_Model extends ORM_Resource 
{
    protected $table_name = "activity_log";

    public $belongs_to = array(
        'profile', 'plugin', 'pluginrelease'
    );

    public $json_fields = array(
        'details', 'old_state', 'new_state'
    );

    public static $current_profile = null;

    /**
     * Find all, with pre-digestion for use in a view.
     */
    public function find_all_for_view()
    {
        $events_db = $this->find_all();
        $events = array();
        foreach ($events_db as $event_db) {
            $event = array_merge($event_db->as_array(), array(
                'profile' => $event_db->profile->as_array(),
                'plugin' => $event_db->plugin->as_array(),
                'plugin_release' => $event_db->pluginrelease->as_array(),
                'isotime' => gmdate('c', strtotime($event_db->created)),
                'relative_date' => 
                    $this->relative_date(strtotime($event_db->created)),
                'time' =>
                    gmdate('g:i:s A', strtotime($event_db->created))
            ));
            if (!empty($event_db->plugin->sandbox_profile_id)) {
                $event['plugin']['sandbox_profile'] = 
                    ORM::factory('profile', $event_db->plugin->sandbox_profile_id)
                        ->as_array();
            }
            $events[] = $event;
        }
        return $events;
    }

    /**
     * Build a relative date display
     * TODO: Move this into a helper
     */
    public function relative_date($time) {
        $today = strtotime(gmdate('M j, Y'));
        $reldays = ($time - $today)/86400;
        if ($reldays >= 0 && $reldays < 1) {
            return 'today';
        } else if ($reldays >= 1 && $reldays < 2) {
            return 'tomorrow';
        } else if ($reldays >= -1 && $reldays < 0) {
            return 'yesterday';
        }
        if (abs($reldays) < 7) {
            if ($reldays > 0) {
                $reldays = floor($reldays);
                return 'in ' . $reldays . ' day' . ($reldays != 1 ? 's' : '');
            } else {
                $reldays = abs(floor($reldays));
                return $reldays . ' day'  . ($reldays != 1 ? 's' : '') . ' ago';
            }
        }
        if (abs($reldays) < 182) {
            return gmdate('l, F j',$time ? $time : time());
        } else {
            return gmdate('l, F j, Y',$time ? $time : time());
        }
    }

    /**
     * Before saving, update with current profile, if necessary.
     */
    public function save() 
    {
        if (empty($this->profile_id) && !empty(self::$current_profile)) {
            $this->profile_id = self::$current_profile->id;
        }
        if (!empty($this->plugin_id)) {
            $this->pfs_id = $this->plugin->pfs_id;
        }
        foreach ($this->json_fields as $name) {
            if (!empty($this->{$name}) && is_array($this->{$name})) {
                $this->{$name} = json_encode($this->{$name});
            }
        }
        return parent::save();
    }

    /**
     * After loading from database, decode the grab bag of attributes from JSON.
     */
    public function load_values(array $values)
    {
        parent::load_values($values);
        foreach ($this->json_fields as $name) {
            if (!empty($this->{$name}) && is_string($this->{$name})) {
                $this->{$name} = json_decode($this->{$name}, TRUE);
            }
        }
    }

}
