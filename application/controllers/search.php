<?php
/**
 * Search controller
 *
 * @package    PluginDir
 * @subpackage controllers
 * @author     l.m.orchard <lorchard@mozilla.com>
 */
class Search_Controller extends Local_Controller {

    /**
     * Home page action
     */
    function index()
    {

    }

    /**
     * Search results page.
     */
    function results()
    {
        $release = ORM::factory('pluginrelease');

        // Exclude sandbox plugins from search results.
        // TODO: Need to add in just the logged in user?
        $release
            ->join('plugins', 'plugin_releases.plugin_id', 'plugins.id')
            ->where('sandbox_profile_id IS NULL');

        if ($p_id = $this->input->get('platform_id')) {
            $release->where('platform_id', $p_id);
        }
        if ($os_id = $this->input->get('os_id')) {
            $release->where('os_id', $os_id);
        }
        if ($mimes_id = $this->input->get('mimes_id')) {
            $release
                ->join('mimes_plugins', 'mimes_plugins.plugin_id', 'plugins.id')
                ->join('mimes', 'mimes.id', 'mimes_plugins.mime_id')
                ->where('mimes.id', $mimes_id);
        }
        if ($q = $this->input->get('q')) {
            $this->view->q = $q;
            $parts = explode(' ', $q);
            foreach ($parts as $part) {
                $clauses = array();
                foreach (array('name', 'description', 'vendor') as $col) {
                    $clauses['plugin_releases.'.$col] = $part;
                }
                $release->orlike($clauses);
            }
        }

        $rows =  $release->find_all();

        $releases = array();
        foreach ($rows as $release) {
            $releases[] = $release->as_array();
        }
        $this->view->releases = $releases;

    }

    /**
     * PFS2 API lookup handler.
     */
    function pfs_v2()
    {
        $this->auto_render = FALSE;

        $params = array(
            'mimetype' => '',
            'clientOS' => '',
            'appID' => '',
            'appVersion' => '',
            'appRelease' => '',
            'chromeLocale' => '',
            'detection' => '',
            'sandboxScreenName' => false,
            'callback' => ''
        );
        foreach ($params as $name=>$default) {
            $params[$name] = $this->input->get($name, $default);
        }

        $callback = $params['callback'];
        unset($params['callback']);

        return json::render(
            ORM::factory('plugin')->lookup($params), $callback
        );
    }

}
