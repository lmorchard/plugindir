/**
 * Main JS package for Plugin Directory
 */
PluginDir = (function () {

    var $this = {

        pfs_endpoint: 'http://dev.plugindir.mozilla.org/api/v1/lookup',

        /**
         * Initialize the package.
         */
        init: function () {

            Pfs.endpoint = $this.pfs_endpoint;

            $(document).ready(function () {

                // Tweak the body tag to indicate JS is working.
                $(document.body).removeClass('noJS').addClass('hasJS');

                // Wire up the advanced search toggle.
                $('#advanced_search_toggle > a').click(function () {
                    $('#advanced_search').toggle();
                    return false;
                });

                // Wire up the status selector to hide/show vulnerability 
                // fields, also setting up the initial state.
                $('#ctrl_plugins_act_submit select#status').each(function () {
                    var status = $(this),
                        fn = function () {
                            $('#vulnerability_url, #vulnerability_description')
                                .parent()
                                .toggle('vulnerable' == status.val());
                        };
                    fn();
                    status.change(fn);
                });

                // If the installed plugins table is present, build it.
                $('table.installed_plugins').each(function () {
                    $this.buildInstalledPluginsTable();
                });

            });

            return this;
        },

        /**
         * Detect installed plugins and render the appropriate table rows.
         */
        buildInstalledPluginsTable: function () {
            var plugins_table = $('table.installed_plugins');

            var browser_plugins = Pfs.UI.browserPlugins(navigator.plugins);

            // First, append rows for the plugins with undetected versions
            $.each(Pfs.UI.unknownVersionPlugins, function () {
                var submit_url, fake_pfs_id,
                    mimes = [],
                    plugin = this;

                // Collect the mimetypes from the unknown plugin.
                for (var i=0; i<plugin.length; i++) {
                    mimes.push(plugin.item(i).type);
                }

                // Build a URL for use in linking to the contribution form,
                // composed of detected plugin details and browser info.
                submit_url = $this.base_url + 'plugins/submit?' +
                    $.param($.extend({
                        status: 'unknown',
                        pfs_id: $this.inventPfsId(plugin),
                        name: plugin.name,
                        filename: plugin.filename,
                        description: plugin.description,
                        version: null,
                        mimetypes: mimes.join("\n")
                    }, Pfs.UI.browserInfo()));

                // Add the table row from template.
                $this.cloneTemplate(
                    plugins_table.find('tr.template'),
                    {
                        ".name": plugin.name,
                        ".description": plugin.description,
                        ".version": 'Not detected (<a href="#">Any ideas?</a>)',
                        '.status': $this.cloneTemplate(
                            $('#status_templates').find('.unknown')
                        ),
                        '.feedback': $this.cloneTemplate(
                            $('#feedback_templates').find('.unknown'),
                            { '@href': submit_url }
                        )
                    }, 
                    plugins_table
                );
            });

            // Next, run each of the plugins with detected versions though PFS2
            Pfs.findPluginInfos(Pfs.UI.browserInfo(), browser_plugins, 
                function (data) {
                    var submit_params, row_data,
                        pfs_id = (data.status == 'unknown') ? null : 
                            data.pfsInfo.releases.latest.pfs_id,
                        plugin = data.pluginInfo.raw,
                        version = Pfs.parseVersion(data.pluginInfo.plugin).join('.');
                        
                    // Build a URL for use in linking to the contribution form,
                    // composed of detected plugin details and browser info.
                    submit_url = $this.base_url + 'plugins/submit?' +
                        $.param($.extend({
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

                    $this.cloneTemplate(
                        plugins_table.find('tr.template'),
                        {
                            // Link the name if there's a known pfs_id
                            '.name': (!pfs_id) ? data.pluginInfo.raw.name :
                                '<a href="'+$this.base_url+'plugins/detail/'+pfs_id+'">' +
                                    data.pluginInfo.raw.name + '</a>',

                            // Link the version if there's a known pfs_id
                            '.version': (!pfs_id) ? version :
                                '<a href="'+$this.base_url+'plugins/detail/'+pfs_id+'#'+version+'">' + 
                                    version + '</a>',
                             
                            // HACK: Use template named for status, fall back
                            // to unknown if not found.
                            '.status': $this.cloneTemplate(
                                $($('#status_templates').find('.'+data.status+',.unknown')[0])
                            ),

                            // HACK: Use template named for status, fall back
                            // to unknown if not found.
                            '.feedback': $this.cloneTemplate(
                                $($('#feedback_templates').find('.'+data.status+',.unknown')[0]),
                                { '@href': submit_url }
                            )
                        }, 
                        plugins_table
                    );
                    
                },
                function () {
                }
            );

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
                .replace(/\./g, '-');
        },

        /**
         * Clone a template element and populate it from a data object, 
         * using the object's keys as CSS selectors and '@'-prefixed names
         * for attributes.  If a parent element is supplied, the resulting
         * cloned element is appended to it.
         *
         * @param   {DOMElement} tmpl   Template element
         * @param   {Object}     data   Template data
         * @param   {DOMElement} [parent] Parent element
         *
         * @returns {DOMElement} The cloned and populated template element
         *
         * TODO: Accept @attributes as part of CSS selectors?
         */
        cloneTemplate: function (tmpl, data, parent) {
            var el = tmpl.clone().removeClass('template');
            for (k in data) if (data.hasOwnProperty(k)) {
                var val = data[k];
                if ('@' === k.substring(0,1)) {
                    el.attr(k.substring(1), val);
                } else {
                    if ('string' === typeof val) {
                        el.find(k).html(val);
                    } else if ('undefined' != typeof val.nodeType) {
                        el.find(k).empty().append(val);
                    }
                }
            }
            if (parent) { el.appendTo(parent); }
            return el[0];
        },

        EOF:null
    };

    return $this.init();

})();
