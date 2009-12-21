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
     * Accept plugin data contributions.
     */
    function submit()
    {
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
     * Display plugin details, accept POST updates.
     */
    function detail($pfs_id, $format='html')
    {
        // Look for the plugin, throw a 404 if not found.
        $plugin = ORM::factory('plugin', $pfs_id);
        if (!$plugin->loaded) {
            return Event::run('system.404');
        }

        if ('json' == $format) {

            if ('post' == request::method()) {

                // Fetch and validate the incoming JSON.
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data || !isset($data['meta'])) {
                    // TODO: Need some better validation of this data.
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }

                // Force the PFS ID to the one requested.
                $data['meta']['pfs_id'] = $pfs_id;

                // Import the plugin JSON.
                Plugin_Model::import($data);

                // Refresh the plugin after import.
                $plugin = ORM::factory('plugin', $pfs_id);

            }
            
            // Return the plugin data as an export in JSON
            $this->auto_render = FALSE;
            $out = $plugin->export();
            return json::render($out, $this->input->get('callback'));
        }

        // Pass the plugin over to the template.
        $this->view->plugin = $plugin->as_array();

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
     * Fire up the plugin editor.
     */
    function edit($pfs_id)
    {
        // Look for the plugin, throw a 404 if not found.
        $plugin = ORM::factory('plugin', $pfs_id);
        if (!$plugin->loaded) {
            return Event::run('system.404');
        }
         
        $this->view->set(array(
            'plugin' => $plugin->as_array(),
            'status_choices' => Plugin_Model::$status_choices,
            'properties' => Plugin_Model::$properties
        ));
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
