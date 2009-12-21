/**
 * JS enhancements for the index page template
 */
/*jslint laxbreak: true */
PluginDir.Index = (function () {

    var $this = {

        /**
         * Initialize the package.
         */
        init: function () {

            $(document).ready(function () {
                // Only act on the index page.
                $('#ctrl_index_act_index').each(function () {

                    // These enhancements are just for "Your Installed Plugins":
                    $('table.installed_plugins').each(function () {
                        var tbl = $(this);
                        // Detect plugins and build the installed table.
                        $this.buildInstalledPluginsTable(tbl);
                        if (PluginDir.is_logged_in) {
                            // Handle clicks to add releases to sandbox plugins.
                            $this.wireUpNewReleaseHandler(tbl);
                        }
                    });

                });
            });

            return this;
        },

        /**
         * Wire up a delegation-based click handler to accept clicks from all
         * the (+) buttons in the add release column.
         */
        wireUpNewReleaseHandler: function (tbl) {
            tbl.click(function (ev) {
                if ('button' == ev.target.tagName.toLowerCase()) {
                    var button = $(ev.target);
                    if (button.parent().hasClass('add_release')) {
                        var select = button.prev('select');
                            url    = select.val();
                        window.location = url;
                    }
                }
            });
        },

        /**
         * Detect installed plugins and render the appropriate table rows.
         */
        buildInstalledPluginsTable: function (plugins_table) {
            Pfs.endpoint = PluginDir.pfs_endpoint;
            var browser_plugins = Pfs.UI.browserPlugins(navigator.plugins);
            var browser_info = Pfs.UI.browserInfo();

            if (PluginDir.is_logged_in) {
                // Include the user's sandbox in PFS lookups, if logged in.
                browser_info.sandboxScreenName = PluginDir.screen_name;
            }

            // Next, run each of the plugins with detected versions though PFS2
            Pfs.findPluginInfos(browser_info, browser_plugins, 

                function (data) {
                    // Callback to process plugins with detected versions.
                    var raw_plugin = data.pluginInfo.raw;
                    var version = Pfs.parseVersion(data.pluginInfo.plugin).join('.');
                    var has_pfs_match = (data.status !== 'unknown');
                    var latest = (!has_pfs_match) ? null : data.pfsInfo.releases.latest;
                    var pfs_id = (!has_pfs_match) ? null : latest.pfs_id;

                    // Come up with a URL for the plugin if there was 
                    // a PFS match.
                    var plugin_url = '';
                    if (has_pfs_match) {
                        plugin_url = 'plugins/detail/' + pfs_id;
                        if (latest.sandbox_profile_screen_name) {
                            // Include profile path if plugin is sandboxed
                            plugin_url = 'profiles/' + 
                                latest.sandbox_profile_screen_name +
                                '/' + plugin_url;
                        }
                        // Finally, append the base path.
                        plugin_url = PluginDir.base_url + plugin_url;
                    }
                        
                    // Build URL params for use in linking to the contribution
                    // and creation forms, composed of detected plugin details
                    // and browser info.
                    var submit_params = $.param($.extend({
                        status: data.status,
                        pfs_id: pfs_id || $this.inventPfsId(raw_plugin),
                        version: version,
                        name: raw_plugin.name,
                        filename: raw_plugin.filename,
                        description: raw_plugin.description,
                        vendor: (!has_pfs_match) ? '' : latest.vendor,
                        mimetypes: data.pluginInfo.mimes.join("\n")
                    }, Pfs.UI.browserInfo()));

                    // Link the name if there's a known PFS match
                    var name_col = (!has_pfs_match) ? raw_plugin.name :
                        '<a href="'+plugin_url+'">' + raw_plugin.name + '</a>';

                    // Link the version if there's a PFS match
                    var version_col = (!has_pfs_match) ? version :
                        '<a href="'+plugin_url+'#'+version+'">' + version + '</a>';

                    var status_col = PluginDir.cloneTemplate(
                        $('#status_templates').find('.'+data.status+',.unknown').eq(0),
                        { '.version': (!has_pfs_match) ? '' : latest.version }
                    );

                    var feedback_col = PluginDir.cloneTemplate(
                        $('#feedback_templates').find('.'+data.status+',.unknown').eq(0),
                        { '@href': PluginDir.base_url+'plugins/submit?'+submit_params }
                    );

                    // If logged in, build the control to add a release to a
                    // sandbox plugin
                    var new_release_col = 
                        $this.buildAddRelease(data, submit_params);

                    // Finally, build and add the new table row.
                    var row = PluginDir.cloneTemplate(
                        plugins_table.find('tr.template'), 
                        {
                            '.name': name_col,
                            '.version': version_col,
                            '.status': status_col,
                            '.feedback': feedback_col,
                            '.new_release': new_release_col
                        }, 
                        plugins_table
                    );

                    // Annotate this result if it was found via sandbox.
                    if (has_pfs_match && latest.sandbox_profile_screen_name) {
                        $(row).addClass("from_sandbox");
                    }
                    
                },

                function () {

                    // After PFS lookups finished, append rows for the plugins
                    // with undetected versions
                    $.each(Pfs.UI.unknownVersionPlugins, function () {
                        var mimes = [], plugin = this;

                        // Collect the mimetypes from the unknown plugin.
                        for (var i=0; i<plugin.length; i++) {
                            mimes.push(plugin[i].type);
                        }

                        // Build a URL for use in linking to the contribution form,
                        // composed of detected plugin details and browser info.
                        var submit_params = $.param($.extend({
                            status: 'unknown',
                            pfs_id: $this.inventPfsId(plugin),
                            name: plugin.name,
                            filename: plugin.filename,
                            description: plugin.description,
                            version: null,
                            mimetypes: mimes.join("\n")
                        }, Pfs.UI.browserInfo()));

                        var submit_url = PluginDir.base_url + 'plugins/submit?' +
                            submit_params;

                        var row_data = {
                            ".name": plugin.name,
                            ".description": plugin.description,
                            // TODO: Need a bugzilla URL or something here for detection ideas
                            ".version": 'Not detected (<a href="#">Any ideas?</a>)',
                            '.status': PluginDir.cloneTemplate(
                                $('#status_templates').find('.unknown')
                            ),
                            '.feedback': PluginDir.cloneTemplate(
                                $('#feedback_templates').find('.unknown'),
                                { '@href': submit_url }
                            ),
                            '.new_release': $this.buildAddRelease({}, submit_params)
                        };

                        // Add the table row from template.
                        PluginDir.cloneTemplate(
                            plugins_table.find('tr.template'),
                            row_data, plugins_table
                        );

                    });

                }
            );

        },

        /**
         * If the user is logged in, build and return a new release control.
         *
         * Also, tack the submission params onto the end of each plugin edit
         * URL in options to provide defaults to the editor.
         */
        buildAddRelease: function (data, submit_params) {
            if (!PluginDir.is_logged_in) {
                return null;
            } else {
                var add_release = $(PluginDir.cloneTemplate($('.add_release')));

                add_release.find('option').each(function () {
                    var option = $(this);
                    var value = option.attr('value');
                    if (value) {
                        option.attr('value', value +
                            '?add_release=1&' + submit_params);
                    }
                });

                return add_release[0];
            }
        },

        /**
         * Try inventing a suggested PFS ID based on plugin filename or name.
         * This is just done in order to make an attempt at a consistent
         * cross-browser ID when building submissions.
         *
         * TODO: Should this be an MD5 hash or something more inclusive of
         * plugin details?
         *
         * @param   {plugin} plugin Plugin from navigator.plugins
         *
         * @returns {string} Generated PFS ID
         */
        inventPfsId: function (plugin) {
            return (plugin.name || plugin.filename || "")
                .toLowerCase()
                .replace(/_/g, '-')
                .replace(/ /g, '-')
                .replace(/\.plugin$/g, '')
                .replace(/\.dll$/g, '')
                .replace(/\.so$/g, '')
                .replace(/\d/g, '')
                .replace(/\./g, '')
                .replace(/-+$/, '')
                ;
        },

        EOF:null
    };

    return $this.init();

})();
