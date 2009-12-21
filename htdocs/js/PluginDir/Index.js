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

            // Next, run each of the plugins with detected versions though PFS2
            Pfs.findPluginInfos(Pfs.UI.browserInfo(), browser_plugins, 
                function (data) {

                    var submit_params, submit_url, row_data,
                        pfs_id = (data.status == 'unknown') ? null : 
                            data.pfsInfo.releases.latest.pfs_id,
                        plugin = data.pluginInfo.raw,
                        version = Pfs.parseVersion(data.pluginInfo.plugin).join('.');
                        
                    // Build a URL for use in linking to the contribution form,
                    // composed of detected plugin details and browser info.
                    submit_params = $.param($.extend({
                        status: data.status,
                        pfs_id: pfs_id || $this.inventPfsId(plugin),
                        name: plugin.name,
                        filename: plugin.filename,
                        vendor: (data.status == 'unknown') ? '' :
                            data.pfsInfo.releases.latest.vendor,
                        description: plugin.description,
                        version: version,
                        mimetypes: data.pluginInfo.mimes.join("\n")
                    }, Pfs.UI.browserInfo()));

                    submit_url = PluginDir.base_url + 'plugins/submit?' + 
                        submit_params;

                    row_data = {
                        // Link the name if there's a known pfs_id
                        '.name': (!pfs_id) ? data.pluginInfo.raw.name :
                            '<a href="'+PluginDir.base_url+'plugins/detail/'+pfs_id+'">' +
                            data.pluginInfo.raw.name + '</a>',
                        // Link the version if there's a known pfs_id
                        '.version': (!pfs_id) ? version :
                            '<a href="'+PluginDir.base_url+'plugins/detail/'+
                                pfs_id+'#'+version+'">' + version + '</a>',
                        '.status': PluginDir.cloneTemplate(
                            // HACK: Use template named for status, fall back
                            // to unknown if not found.
                            $($('#status_templates').find('.'+data.status+',.unknown')[0])
                        ),
                        '.feedback': PluginDir.cloneTemplate(
                            // HACK: Use template named for status, fall back
                            // to unknown if not found.
                            $($('#feedback_templates').find('.'+data.status+',.unknown')[0]),
                            { '@href': submit_url }
                        ),
                        '.new_release': $this.buildAddRelease(submit_params)
                    };

                    // Finally, build and add the new table row.
                    PluginDir.cloneTemplate(
                        plugins_table.find('tr.template'), 
                        row_data, plugins_table
                    );
                    
                },

                function () {

                    // After detection finished, append rows for the plugins
                    // with undetected versions
                    $.each(Pfs.UI.unknownVersionPlugins, function () {
                        var submit_params, submit_url, row_data, fake_pfs_id,
                            mimes = [],
                            plugin = this;

                        // Collect the mimetypes from the unknown plugin.
                        for (var i=0; i<plugin.length; i++) {
                            mimes.push(plugin[i].type);
                        }

                        // Build a URL for use in linking to the contribution form,
                        // composed of detected plugin details and browser info.
                        submit_params = $.param($.extend({
                            status: 'unknown',
                            pfs_id: $this.inventPfsId(plugin),
                            name: plugin.name,
                            filename: plugin.filename,
                            description: plugin.description,
                            version: null,
                            mimetypes: mimes.join("\n")
                        }, Pfs.UI.browserInfo()));

                        submit_url = PluginDir.base_url + 'plugins/submit?' +
                            submit_params;

                        row_data = {
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
                            '.new_release': $this.buildAddRelease(submit_params)
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
        buildAddRelease: function (submit_params) {
            if (!PluginDir.is_logged_in) {
                return null;
            } else {
                var add_release = PluginDir.cloneTemplate($('.add_release'));
                $(add_release).find('option').each(function () {
                    var option = $(this),
                        value = option.attr('value');
                    if (value) {
                        option.attr('value', value + 
                            '?add_release=1&' + submit_params);
                    }
                });
                return add_release;
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
