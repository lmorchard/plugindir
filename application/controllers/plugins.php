<?php
/**
 * Plugin controller
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Plugins_Controller extends Local_Controller {

    /**
     * Constructor, runs before any method.
     */
    public function __construct() 
    {
        parent::__construct();
    }


    /**
     * Accept plugin data contributions.
     */
    function submit()
    {
        if (!authprofiles::is_allowed('plugin', 'submit_plugin'))
            return Event::run('system.forbidden');

        $this->view->status_choices = Plugin_Model::$status_choices;

        // Just display the populated form on GET.
        if ('post' != request::method()) {
            form::$data = $_GET;
            return;
        }

        // The only requirement is that the captcha is valid.
        if (!Captcha::valid($this->input->post('captcha'))) {
            form::$data = $_POST;
            form::$errors = array(
                'captcha' => 'Valid captcha response is required'
            );
            return;
        }

        // Save the form data as a plugin submission.
        $submission = ORM::factory('submission')
            ->set($this->input->post())
            ->save();

        $this->view->saved = TRUE;
    }

    /**
     * User's plugin sandbox
     */
    function sandbox($screen_name) {
        $profile = ORM::factory('profile', $screen_name);
        if (!$profile->loaded)
            return Event::run('system.404');
        if (!authprofiles::is_allowed($profile, 'view_sandbox'))
            return Event::run('system.forbidden');

        $this->view->profile = $profile->as_array();

        $this->view->sandbox_plugins = ORM::factory('plugin')
            ->where('sandbox_profile_id', $profile->id)
            ->orderby('modified','DESC')
            ->find_all()->as_array();

        if ($screen_name == authprofiles::get_profile('screen_name')) {
            // HACK: Since this is using the index page shell, inform it which tab 
            // to select.  This should probably be done all in the view.
            $this->view->by_cat = 'sandbox';
            $this->view->set_filename('plugins/sandbox_mine');
        }
    }

    /**
     * Display plugin details, accept POST updates.
     */
    function detail($pfs_id, $format='html', $screen_name=null)
    {
        $plugin = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'view'))
            return Event::run('system.forbidden');

        if ('json' == $format) {

            if ('post' == request::method()) {
                if (!authprofiles::is_allowed($plugin, 'edit'))
                    return Event::run('system.forbidden');

                // Fetch and validate the incoming JSON.
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data || !isset($data['meta'])) {
                    // TODO: Need some better validation of this data.
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }

                // Force some significant details in export to match original plugin.
                $force_names = array(
                    'pfs_id', 'sandbox_profile_id', 'original_plugin_id'
                );
                foreach ($force_names as $name) {
                    if (!empty($plugin->{$name})) {
                        $data['meta'][$name] = $plugin->{$name};
                    }
                }
                
                $plugin = ORM::factory('plugin')->import($data);
            }
            
            // Return the plugin data as an export in JSON
            $this->auto_render = FALSE;
            $out = $plugin->export();
            return json::render($out, $this->input->get('callback'));
        }

        // Collate the plugin releases by version.
        $releases = array();
        foreach ($plugin->pluginreleases as $release) {
            if (!isset($releases[$release->version])) {
                $releases[$release->version] = array();
            }
            $releases[$release->version][] = $release->as_array();
        }

        // Do a rough version sort.
        uksort($releases, array($this, '_versionCmp'));
        
        $this->view->releases = $releases;
    }

    /**
     * Copy a plugin to user's sandbox
     */
    function copy($pfs_id, $screen_name=null)
    {
        $plugin = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'copy'))
            return Event::run('system.forbidden');

        // Only perform the copy on POST.
        if ('post' == request::method()) {

            // Get an export of the original
            $export = $plugin->export();

            // Assign the export to the requesting user's sandbox.
            $export['meta']['sandbox_profile_id'] = authprofiles::get_profile('id');
            
            // If not already set (eg. copy of a copy), assign the original 
            // plugin ID
            if (empty($export['meta']['original_plugin_id'])) {
                $export['meta']['original_plugin_id'] = $plugin->id;
            }
            
            // Create a new plugin from the export.
            $new_plugin = ORM::factory('plugin')->import($export);

            // Bounce over to sandbox.
            $auth_screen_name = authprofiles::get_profile('screen_name');
            url::redirect("profiles/{$auth_screen_name}/plugins");

        }
    }

    /**
     * Deploy a sandbox plugin live.
     */
    function deploy($pfs_id, $screen_name=null) {
        $plugin = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'deploy'))
            return Event::run('system.forbidden');

        if (!$plugin->sandbox_profile_id) {
            // Only sandboxed plugins can be deployed.
            return Event::run('system.forbidden');
        }

        // Only perform the copy on POST.
        if ('post' == request::method()) {

            // Get an export of the original
            $export = $plugin->export();

            // Discard sandbox details, which will replace the original.
            unset($export['meta']['sandbox_profile_id']);
            unset($export['meta']['original_plugin_id']);
            
            // Import the export to finish deployment.
            $new_plugin = ORM::factory('plugin')->import($export);

            // Bounce over to the deployed plugin
            url::redirect("plugins/detail/{$new_plugin->pfs_id}");

        }
    }

    /**
     * Delete a plugin.
     */
    function delete($pfs_id, $screen_name=null) 
    {
        $plugin = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'delete'))
            return Event::run('system.forbidden');

        // Only perform the delete on POST.
        if ('post' == request::method()) {

            $plugin->delete();

            // Bounce over to sandbox.
            $auth_screen_name = authprofiles::get_profile('screen_name');
            url::redirect("profiles/{$auth_screen_name}/plugins");
        }
    }

    /**
     * Fire up the plugin editor.
     */
    function edit($pfs_id, $screen_name=null)
    {
        $plugin = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'edit'))
            return Event::run('system.forbidden');

        $this->view->set(array(
            'status_choices' => Plugin_Model::$status_choices,
            'properties' => Plugin_Model::$properties
        ));
    }


    /**
     * Try looking for plugin given PFS ID and optional screen name.
     * Throws a 404 event if not found, and exits.
     * Also shoves the plugin and screen name into the view variables.
     */
    function _find_plugin($pfs_id, $screen_name=null)
    {
        // Check for screen name if necessary
        if (!$screen_name) {
            $profile_id = null;
        } else {
            $profile = ORM::factory('profile', $screen_name);
            if (!$profile->loaded) {
                Event::run('system.404');
                exit;
            }
            $profile_id = $profile->id;
        }

        // Look for the plugin, throw a 404 if not found.
        $plugin = ORM::factory('plugin', array(
            'pfs_id' => $pfs_id, 'sandbox_profile_id' => $profile_id
        ));
        if (!$plugin->loaded) {
            Event::run('system.404');
            exit;
        }

        $this->view->plugin = $plugin;
        $this->view->screen_name = $screen_name;

        return $plugin;
    }

    /**
     * Munge and compare two versions for sorting.
     */
    function _versionCmp($a, $b) {
        $am = $this->_versionMunge($a);
        $bm = $this->_versionMunge($b);
        return strcmp($bm, $am);
    }

    /**
     * For rough string comparisons, split versions on dots and pad out each 
     * component with zeroes to 5 digits.
     */
    function _versionMunge($v) {
        $parts = explode('.', $v);
        $out = array();
        foreach ($parts as $part) {
            $out[] = substr('00000' . $part, -5, 5);
        }
        return join('.', $out);
    }

}
