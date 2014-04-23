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

        // Protect some methods by requiring an anti-CSRF crumb on POST.
        $crumb_methods = array(
            'create', 'edit', 'detail', 'copy', 'deploy', 'delete',
            'requestpush', 'addtrusted', 'removetrusted'
        );
        if (in_array(Router::$method, $crumb_methods)) {

            // Generate a crumb for these methods.
            $this->view->crumb = csrf_crumbs::generate();

            // Require a valid crumb on POST requests.
            if ('post' == request::method()) {
                // TODO: Disable this if/when details becomes an external API
                $crumb = $this->input->server('HTTP_X_CSRF_CRUMB', null);
                if (empty($crumb)) $crumb = $this->input->post('crumb');
                if (!csrf_crumbs::validate($crumb)) {
                    Router::$method = 'invalidcrumb';
                }
            }

        }
    }

    /**
     * Produce a JSON index of all plugins with release counts.
     */
    function index_json()
    {
        $this->auto_render = FALSE;
        $name_counts = ORM::factory('plugin')->find_release_counts();
        $out = array();
        foreach ($name_counts as $count) {
            $out[] = array(
                'pfs_id'        => $count->pfs_id,
                'name'          => $count->name,
                'release_count' => $count->count,
                'description'   => $count->description,
                'modified'      => $count->modified,
                'href'          => url::site('plugins/detail/' .
                    $count->pfs_id . '.json')
            );
        }
        return json::render($out, $this->input->get('callback'));
    }

    /**
     * Offer a JSON API to the PFS ID suggestion method.
     */
    function suggest_pfs_id()
    {
        $this->auto_render = FALSE;

        $params = array(
            'mimetype' => '',
            'filename' => false,
            'name' => false,
            'vendor' => false,
            'callback' => false,
        );
        foreach ($params as $name=>$default) {
            $params[$name] = $this->input->get($name, $default);
        }

        $callback = $params['callback'];
        unset($params['callback']);

        return json::render(
            ORM::factory('plugin')->suggestPfsId($params), $callback
        );
    }

    /**
     * Create a new plugin, either in public or in a sandbox.
     */
    function create($screen_name=null)
    {
        $profile = ORM::factory('profile', $screen_name);
        $resource = ($profile->loaded) ? $profile : 'profile';
        if (!authprofiles::is_allowed($resource, 'create_in_sandbox'))
            return Event::run('system.forbidden');

        $this->view->profile = ($profile->loaded) ? $profile : null;
        $this->view->status_choices = Plugin_Model::$status_choices;

        // Just display the populated form on GET.
        if ('post' != request::method()) {
            $this->view->form_data = $_GET;
            return;
        }

        $import = array(
            'meta' => array(
                'pfs_id' => $this->input->post('pfs_id'),
                'name' => $this->input->post('name'),
                'filename' => $this->input->post('filename'),
                'vulnerability_url' =>
                    $this->input->post('vulnerability_url'),
                'vulnerability_description' =>
                    $this->input->post('vulnerability_description'),
            ),
            'aliases' => array(),
            'mimes' => preg_split("/\s+/", $this->input->post('mimetypes')),
            'releases' => array(
                array(
                    'status' => $this->input->post('status'),
                    'version' => $this->input->post('version'),
                    'detected_version' => $this->input->post('detected_version'),
                    'detection_type' => $this->input->post('detection_type'),
                    'os_name' => $this->input->post('clientOS'),
                    'platform' => array(
                        'app_id' => $this->input->post('appID'),
                        'app_release' => $this->input->post('appRelease'),
                        'app_version' => $this->input->post('appVersion'),
                        'locale' =>  $this->input->post('chromeLocale'),
                    )
                )
            ),
        );

        if ($profile->loaded) {
            $import['meta']['sandbox_profile_id'] = $profile->id;
        }

        // Create a new plugin from the export.
        $new_plugin = ORM::factory('plugin')->import($import);

        // Log the plugin creation
        ORM::factory('auditLogEvent')->set(array(
            'action'    => 'created',
            'plugin_id' => $new_plugin->id,
            'new_state' => $import,
        ))->save();

        // Bounce over to the created plugin
        if ($new_plugin->sandbox_profile_id) {
            url::redirect(
                "profiles/{$profile->screen_name}/plugins".
                "/detail/{$new_plugin->pfs_id};edit"
            );
        } else {
            url::redirect(
                "plugins/detail/{$new_plugin->pfs_id};edit"
            );
        }

    }

    /**
     * User's plugin sandbox
     */
    function sandbox($screen_name, $format='html') {
        $profile = ORM::factory('profile', $screen_name);
        if (!$profile->loaded)
            return Event::run('system.404');
        if (!authprofiles::is_allowed($profile, 'view_sandbox'))
            return Event::run('system.forbidden');

        $this->view->profile = $profile;

        $this->view->sandbox_plugins = $plugins = ORM::factory('plugin')
            ->where('sandbox_profile_id', $profile->id)
            ->orderby('modified','DESC')
            ->find_all()->as_array();

        if ('json' == $format) {
            $this->auto_render = FALSE;
            $out = array();
            foreach ($plugins as $plugin) {
                $base = url::site(
                    'profiles/'.$profile->screen_name.
                    '/plugins/detail/'.
                    $plugin->pfs_id
                );
                $out[] = array(
                    'pfs_id' => $plugin->pfs_id,
                    'name'   => $plugin->name,
                    'view'   => $base,
                    'edit'   => $base . ';edit',
                    'data'   => $base . '.json'
                );
            }
            return json::render($out, $this->input->get('callback'));
        }

        if ($screen_name == authprofiles::get_profile('screen_name')) {
            // HACK: Since this is using the index page shell, inform it which tab
            // to select.  This should probably be done all in the view.
            $this->view->events = ORM::factory('auditLogEvent')
                ->orderby(array('created'=>'DESC'))
                ->where('profile_id', $profile->id)
                ->limit(15)
                ->find_all_for_view();
            $this->view->by_cat = 'sandbox';
            $this->view->set_filename('plugins/sandbox_mine');
        }
    }

    /**
     * Display the activity log for a plugin
     */
    function activitylog($pfs_id=null, $screen_name=null)
    {
        if (null == $pfs_id) {
            $plugin = null;
        } else {
            list($plugin, $plugin_profile) =
                $this->_find_plugin($pfs_id, $screen_name);
            if (!authprofiles::is_allowed($plugin, 'view'))
                return Event::run('system.forbidden');
        }

        $per_page  = $this->input->get('limit', 25);
        $curr_page = $this->input->get('page', 1);

        if (null == $plugin) {
            $total_items = ORM::factory('auditLogEvent')->count_all();
        } else {
            $total_items = ORM::factory('auditLogEvent')
                ->where('plugin_id', $plugin->id)->count_all();
        }

        $pagination = new Pagination(array(
            'base_url' => url::current(),
            'total_items' => $total_items,
            'items_per_page' => $per_page,
            'query_string' => 'page'
        ));

        $events = ORM::factory('auditLogEvent')
            ->orderby(array('created'=>'DESC'));
        if ($plugin)
            $events->where('plugin_id', $plugin->id);
        $events->limit($pagination->items_per_page, $pagination->sql_offset);

        $this->view->set(array(
            'screenname' => $screen_name,
            'plugin'     => $plugin,
            'pagination' => $pagination,
            'events'     => $events->find_all_for_view()
        ));
    }

    /**
     * Display plugin details, accept POST updates.
     */
    function detail($pfs_id, $format='html', $screen_name=null)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'view'))
            return Event::run('system.forbidden');

        if (!empty($screen_name)) {
            // Try finding the live version of this plugin, since it was looked
            // for in a sandbox.
            $live_plugin = ORM::factory('plugin')
                ->where('pfs_id', $pfs_id)
                ->where('sandbox_profile_id IS NULL')
                ->find();
            $this->view->live_plugin = ($live_plugin->loaded) ?
                $live_plugin : null;
        }

        if ('json' == $format || 'rawjson' == $format) {

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

                list($is_valid, $errors) = ORM::factory('plugin')->validate($data);
                if (!$is_valid) {
                    header('HTTP/1.1 400 Bad Request');
                    $this->auto_render = FALSE;
                    return json::render(
                        array('errors'=>$errors),
                        $this->input->get('callback')
                    );
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

                $old_state = $plugin->export();

                $updated_plugin = ORM::factory('plugin')->import($data);

                // Log the plugin modification
                ORM::factory('auditLogEvent')->set(array(
                    'action'    => 'modified',
                    'plugin_id' => $plugin->id,
                    'old_state' => $old_state,
                    'new_state' => $data,
                ))->save();

            }

            // Return the plugin data as an export in JSON
            $this->auto_render = FALSE;
            $out = $plugin->export();

            if ('rawjson' == $format) {
                return $out;
            } else {
                return json::render($out, $this->input->get('callback'));
            }
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

        $this->view->events = ORM::factory('auditLogEvent')
            ->orderby(array('created'=>'DESC'))
            ->where('plugin_id', $plugin->id)
            ->limit(15)
            ->find_all_for_view();

        $this->view->releases = $releases;
    }

    /**
     * Java version numbers have a 1 prepended to the actual release
     * number, for example: 1.7.5.25. Here, we return the actual
     * version number i.e. 7.5.25
     */
    function fix_java_version_string($releases) {

        foreach ($releases as $index => $release) {

            $release['detected_version'] = substr($release['detected_version'], 1);
            $release['version'] = substr($release['version'], 1);
            // Stick the amended array back onto the main container.
            $releases[$index] = $release;
        }

        return $releases;
    }

    function sort_releases_by_os($releases) {

        $releases_by_os = array();
        $all = array();
        $win = array();
        $mac = array();
        $lin = array();

        foreach ($releases as $release) {
            switch ($release['os_name']) {
                case '*':
                    $all[] = $release;
                    break;
                case 'win':
                case 'Windows':
                case 'windows nt 6.0':
                    $win[] = $release;
                    break;
                case 'mac':
                case 'PPC Mac OS X 10.*':
                case 'Intel Mac OS X 10.3':
                case 'Intel Mac OS X 10.4':
                case 'Intel Mac OS X 10.5':
                case 'intel mac os x 10.6':
                case 'Intel Mac OS X 10.7':
                case 'Intel Mac OS X 10.8':
                    $mac[] = $release;
                    break;
                case 'linux':
                    $lin[] = $release;
                    break;
            }
        }

        $releases_by_os['all'] = $all;
        $releases_by_os['win'] = $win;
        $releases_by_os['mac'] = $mac;
        $releases_by_os['lin'] = $lin;

        return $releases_by_os;
    }

    /**
     * Return all latest and vulnerable releases.
     */
    function get_releases_by_status($releases)
    {
        $filtered_releases = array();
        $filtered_releases['latest'] = array();
        $filtered_releases['vulnerable'] = array();

        foreach ($releases as $release) {
            if ($release['status'] == 'latest' && !array_key_exists('vulnerability_url', $release)) {
                $filtered_releases['latest'][] = $release;
            }

            if ($release['status'] == 'vulnerable' && isset($release['vulnerability_url'])) {
                $filtered_releases['vulnerable'][] = $release;
            }
        }

        return $filtered_releases;
    }

    /**
     * Returns releases filtered on status.
     * returned: latest, vulnerable
     */
    function filter_releases_by_plugin($releases_by_os)
    {
        $filtered_releases = array();

        foreach ($releases_by_os as $key => $release) {
            $filtered_releases[$key] = $this::get_releases_by_status($releases_by_os[$key]);
        }

        return $filtered_releases;
    }

    /**
     * Returnes information regarding all plugins in the $plugin_names array.
     */
    function get_all_plugins($plugin_names)
    {
        $plugins = array();
        $mimes = array();

        foreach ($plugin_names as $plugin) {
            $current_plugin = $this::detail($plugin['pfs_id'], 'rawjson');

            $all_releases = $current_plugin['releases'];

            $url = '';
            if (isset($current_plugin['meta']['url'])) {
                $url = $current_plugin['meta']['url'];
            }

            $regex = '';
            if (isset($current_plugin['aliases']['regex'])) {
                $regex = $current_plugin['aliases']['regex'];
            }

            $description = '';
            if (isset($current_plugin['meta']['description'])) {
                $description = $current_plugin['meta']['description'];
            }

            // Sort versions from highest to lowest.
            usort($all_releases, function($arelease, $brelease) {
                return version_compare($brelease['version'], $arelease['version']);
            });

            if (preg_match('/.*Java Runtime.*$/', $current_plugin['meta']['name'])) {
                $all_releases = $this::fix_java_version_string($all_releases);
            }

            $plugin_releases_by_os = $this::sort_releases_by_os($all_releases);

            $filtered_plugin_releases_by_os = $this::filter_releases_by_plugin($plugin_releases_by_os);

            foreach ($current_plugin['mimes'] as $mime) {
                $mimes[] = $mime;
            }

            $plugins['plugins'][$current_plugin['meta']['pfs_id']] = array(
                'display_name' => $current_plugin['meta']['name'],
                'description'  => $description,
                'versions'     => $filtered_plugin_releases_by_os,
                'mimes'        => $current_plugin['mimes'],
                'url'          => $url,
                'regex'        => $regex
            );
        }

        $plugins['mime_types'] = $mimes;

        return $plugins;
    }

    /**
     * Returns a JSON response containing all plugins in the database, with the
     * information specified in https://bugzilla.mozilla.org/show_bug.cgi?id=956905
     */
    function plugins_list()
    {
        $this->auto_render = FALSE;
        $name_counts = ORM::factory('plugin')->find_release_counts();
        $plugin_ids = array();

        # Get all plugin pfs_ids
        foreach ($name_counts as $plugin) {
            $plugin_ids[] = array(
                'pfs_id' => $plugin->pfs_id
            );
        }

        $plugins = $this::get_all_plugins($plugin_ids);

        return json::render($plugins, $this->input->get('callback'));
    }

    /**
     * Copy a plugin to user's sandbox
     */
    function copy($pfs_id, $screen_name=null)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
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

            // Log the sandbox copy.
            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'copied_to_sandbox',
                'plugin_id' => $plugin->id,
                'old_state' => $export,
                'details'   => array(
                    'sandbox_profile' => authprofiles::get_profile()->as_array()
                ),
            ))->save();

            $auth_screen_name = authprofiles::get_profile('screen_name');
            if (empty($_GET)) {
                // Bounce over to sandbox.
                url::redirect("profiles/{$auth_screen_name}/plugins");
            } else {
                // GET params not empty, so bounce them over to editor
                $qs = http_build_query($_GET);
                url::redirect("profiles/{$auth_screen_name}/plugins/detail/{$new_plugin->pfs_id};edit?{$qs}");
            }

        }
    }

    /**
     * Deploy a sandbox plugin live.
     */
    function deploy($pfs_id, $screen_name=null) {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
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
            $export['meta']['sandbox_profile_id'] = null;
            $export['meta']['original_plugin_id'] = null;

            // Import the export to finish deployment.
            $new_plugin = ORM::factory('plugin')->import($export);

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'deployed_from_sandbox',
                'old_state' => $export,
                'plugin_id' => $plugin->id,
            ))->save();

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'deployed_from_sandbox',
                'plugin_id' => $new_plugin->id,
                'details'   => array(
                    'sandbox_profile'=>$plugin_profile->as_array()
                ),
                'new_state' => $new_plugin->export(),
            ))->save();

            // Bounce over to the deployed plugin
            url::redirect("plugins/detail/{$new_plugin->pfs_id}");

        }
    }

    /**
     * Delete a plugin.
     */
    function delete($pfs_id, $screen_name=null)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'delete'))
            return Event::run('system.forbidden');

        // Only perform the delete on POST.
        if ('post' == request::method()) {

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'deleted',
                'plugin_id' => $plugin->id,
                'old_state' => $plugin->export(),
            ))->save();

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
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'edit'))
            return Event::run('system.forbidden');

        $this->view->set(array(
            'status_choices' => Plugin_Model::$status_choices,
            'properties' => Plugin_Model::$properties
        ));
    }

    /**
     * Request review of plugin changes to be pushed live.
     */
    function requestpush($pfs_id, $screen_name=null)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'requestpush'))
            return Event::run('system.forbidden');

        // Only perform the delete on POST.
        if ('post' == request::method()) {

            $emails = array();
            $watchers = ORM::factory('profile')
                ->find_all_by_role(array('editor', 'admin'));
            foreach ($watchers as $profile) {
                $emails[] = $profile->find_default_login_for_profile()->email;
            }

            email::send_view(
                array(
                    'to' => $emails
                ),
                'plugins/requestpush_email',
                array(
                    'plugin' => $plugin,
                    'screen_name' => $screen_name
                )
            );

            Session::instance()
                ->set_flash('message', 'Approval requested');

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'requested_push',
                'plugin_id' => $plugin->id,
                'old_state' => $plugin->export(),
            ))->save();

            // Bounce over to sandbox.
            $auth_screen_name = authprofiles::get_profile('screen_name');
            url::redirect(
                "profiles/{$auth_screen_name}/plugins/detail/{$plugin->pfs_id}"
            );
        }
    }

    /**
     * Attempt to add a profile as trusted for the plugin.
     */
    function addtrusted($pfs_id, $screen_name)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'managetrust'))
            return Event::run('system.forbidden');

        // Only perform the delete on POST.
        if ('post' == request::method()) {
            $plugin->add_trusted($plugin_profile);

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'add_trusted',
                'details'   => array(
                    'profile' => $plugin_profile->as_array()
                ),
                'plugin_id' => $plugin->id,
                'old_state' => $plugin->export(),
            ))->save();

            // Bounce over to sandbox.
            $auth_screen_name = authprofiles::get_profile('screen_name');
            url::redirect(
                "profiles/{$screen_name}/plugins/detail/{$plugin->pfs_id}"
            );
        }
    }

    /**
     * Attempt to remove a profile as trusted for the plugin.
     */
    function removetrusted($pfs_id, $screen_name)
    {
        list($plugin, $plugin_profile) = $this->_find_plugin($pfs_id, $screen_name);
        if (!authprofiles::is_allowed($plugin, 'managetrust'))
            return Event::run('system.forbidden');

        // Only perform the delete on POST.
        if ('post' == request::method()) {
            $plugin->remove_trusted($plugin_profile);

            ORM::factory('auditLogEvent')->set(array(
                'action'    => 'remove_trusted',
                'details'   => array(
                    'profile' => $plugin_profile->as_array()
                ),
                'plugin_id' => $plugin->id,
                'old_state' => $plugin->export(),
            ))->save();

            // Bounce over to sandbox.
            $auth_screen_name = authprofiles::get_profile('screen_name');
            url::redirect(
                "profiles/{$screen_name}/plugins/detail/{$plugin->pfs_id}"
            );
        }
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
            $profile = null;
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
        $this->view->plugin_profile = $profile;
        $this->view->screen_name = $screen_name;

        return array($plugin, $profile);
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
