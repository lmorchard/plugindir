/**
 * JS enhancements for the index page template
 */
/*jslint laxbreak: true */
PluginDir.Index = (function () {

    var $ = jQuery.noConflict();
    var $this = {

        /**
         * Initialize the package.
         */
        init: function () {

            $(document).ready(function () {
                // Only act on the index page.
                $('#ctrl_index_act_index').each(function () {
                    if (!PluginDir.is_logged_in) {
                        // If not logged in, continue on to second stage of
                        // initialization.
                        return $this.init_2();
                    } else {
                        // If logged in, load up sandbox plugins before
                        // continuing initialization.
                        $.getJSON(PluginDir.sandbox_url, {}, function (data) {
                            $this.sandbox_plugins = data;
                            $this.init_2();
                        });
                    }
                });
            });

            return this;
        },

        /**
         * Second phase of initialization, after sandbox plugins have been 
         * loaded or skipped.
         */
        init_2: function () {
            // These enhancements are just for "Your Installed Plugins":
            $('table.installed_plugins').each(function () {
                var tbl = $(this);
                // Detect plugins and build the installed table.
                $this.buildInstalledPluginsTable(tbl);
                if (PluginDir.is_logged_in) {
                    // Handle clicks to sandbox actions buttons.
                    $('.add_release button').live('click', function (ev) {
                        window.location = $(this).prev('select').val();
                    });
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
                // TODO: Allow disabling of this via a query string param?
                browser_info.sandboxScreenName = PluginDir.screen_name;
            }

            // Next, run each of the plugins with detected versions though PFS2
            Pfs.findPluginInfos(browser_info, browser_plugins, 

                function (data) {
                    // Callback to process plugins with detected versions.
                    var raw_plugin = data.pluginInfo.raw;
                    var version = Pfs.parseVersion(data.pluginInfo.plugin).join('.');
                    var has_pfs_match = (data.status !== 'unknown') && data.pfsInfo;
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

                    var status_col = PluginDir.Utils.cloneTemplate(
                        $('#status_templates').find('.'+data.status+',.unknown').eq(0),
                        { '.version': (!has_pfs_match) ? '' : latest.version }
                    );

                    var feedback_col = PluginDir.Utils.cloneTemplate(
                        $('#feedback_templates').find('.'+data.status+',.unknown').eq(0),
                        { '@href': PluginDir.base_url+'plugins/submit?'+submit_params }
                    );

                    // If logged in, build the control to add a release to a
                    // sandbox plugin
                    var new_release_col = 
                        $this.buildSandboxActions(data, plugin_url, submit_params);

                    // Finally, build and add the new table row.
                    var row = PluginDir.Utils.cloneTemplate(
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
                        $(row)
                            .addClass("from_sandbox")
                            .find('.name')
                                .append('<div>' + _("(from sandbox)") + '</div>')
                            .end();
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
                            ".version": _("Not detected (<a href=\"https://bugzilla.mozilla.org/show_bug.cgi?id=536394\" target=\"_new\">Any ideas?</a>)"),
                            '.status': PluginDir.Utils.cloneTemplate(
                                $('#status_templates').find('.unknown')
                            ),
                            '.feedback': PluginDir.Utils.cloneTemplate(
                                $('#feedback_templates').find('.unknown'),
                                { '@href': submit_url }
                            ),
                            '.new_release': $this.buildSandboxActions({}, '', submit_params)
                        };

                        // Add the table row from template.
                        PluginDir.Utils.cloneTemplate(
                            plugins_table.find('tr.template'),
                            row_data, plugins_table
                        );

                    });

                    $("tr:not(.template):nth-child(odd)").addClass("odd");
                    $("tr:not(.template):nth-child(even)").addClass("even");

                }
            );

        },

        /**
         * If the user is logged in, build and return a new release control.
         *
         * Also, tack the submission params onto the end of each plugin edit
         * URL in options to provide defaults to the editor.
         */
        buildSandboxActions: function (data, plugin_url, submit_params) {

            if (!PluginDir.is_logged_in) {
                // No control for logged out users
                return null;
            }
            
            var has_pfs_match = (data.status !== 'unknown') && data.pfsInfo;
            var latest = (!has_pfs_match) ? null : data.pfsInfo.releases.latest;
            var pfs_id = (!has_pfs_match) ? null : latest.pfs_id;

            var add_release = PluginDir.Utils.cloneTemplate($('.add_release'));
            var select = $(add_release).find('select')[0];

            if (has_pfs_match && !latest.sandbox_profile_screen_name) {
                
                // If PFS matched but not sandboxed, allow copy to sandbox.
                select.options[0] = new Option(_("Copy to sandbox"), plugin_url+';copy');
            
            } else if (has_pfs_match && latest.sandbox_profile_screen_name) {
                
                // If PFS matched and in sandbox, allow edit in sandbox.
                // TODO: Maybe need sandbox plugins indexed by PFS ID from server?
                $.each($this.sandbox_plugins, function () {
                    if (this.pfs_id == pfs_id) {
                        select.options[0] = new Option(_("Edit in sandbox"), this.edit);
                    }
                });

            } else {

                // Neither PFS matched nor in sandbox, so provide options to
                // create a new plugin in the sandbox, or to add this detected
                // release to an existing sandbox plugin.
                select.options[0] = new Option(
                    _("Create new sandbox plugin"),
                    PluginDir.base_url + 'profiles/'+ PluginDir.screen_name + 
                        '/plugins;create?' + submit_params
                );
                
                $.each($this.sandbox_plugins, function (i) {
                    select.options[i+1] = new Option(
                        sprintf(_("Add release to %1$s"), this.name), 
                        this.edit + '?add_release=1&' + submit_params
                    );
                })

            }

            return add_release;
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
