/**
 * Main JS package for Plugin Directory
 */
PluginDir = (function () {

    var pfs_endpoint = 'http://pfs2.mozilla.org/';

    var $this = {

        /**
         * Initialize the package.
         */
        init: function () {

            Pfs.endpoint = pfs_endpoint;

            $(document).ready(function () {

                $('#advanced_search_toggle > a').click(function () {
                    $('#advanced_search').toggle();
                    return false;
                });

                // If the installed plugins table is present, build it.
                $('table.installed_plugins')
                    .each($this.buildInstalledPluginsTable);

            });

            return this;
        },

        /**
         * Detect installed plugins and render the appropriate table rows.
         */
        buildInstalledPluginsTable: function () {
            var plugins_table = $(this);

            var browser_plugins = Pfs.UI.browserPlugins(navigator.plugins);

            // First, append rows for the plugins with undetected versions
            $.each(Pfs.UI.unknownVersionPlugins, function () {
                var plugin = this;

                $this.appendTemplate(
                    plugins_table.find('tr.template'), plugins_table,
                    {
                        ".name": plugin.name,
                        ".description": plugin.description,
                        ".version": 'Not detected (<a href="#">Any ideas?</a>)',
                        ".status": 'Unknown',
                        ".action": '<a href="#">Contribute info</a>'
                    }
                );
            });

            // Next, run each of the plugins with detected versions though PFS2
            Pfs.findPluginInfos(Pfs.UI.browserInfo(), browser_plugins, 
                function (data) {

                    var pfs_id   = null,
                        version  = Pfs.parseVersion(data.pluginInfo.plugin).join('.'),
                        row_data = {};

                    if (data.status == 'unknown') {
                        // Unknown status plugins don't have a PFS ID.
                        row_data = {
                            '.name': data.pluginInfo.raw.name,
                            '.version': version
                        };
                    } else {
                        // Known status plugins have PFS IDs, so build detail page links.
                        pfs_id = data.pfsInfo.releases.latest.pfs_id;
                        row_data = {
                            '.name': '<a href="'+$this.base_url+'plugins/'+pfs_id+'">' + 
                                data.pluginInfo.raw.name + '</a>',
                            '.version': '<a href="'+$this.base_url+'plugins/'+pfs_id+'#'+version+'">' + 
                                version + '</a>'
                        };
                    }

                    // TODO: Embed these as localized templates in the HTML?
                    switch (data.status) {
                        case 'newer':
                            row_data['.status'] = 'Newer';
                            row_data['.action'] = '<a href="#">Suggest update</a>'; 
                            break;
                        case 'latest':
                            row_data['.status'] = 'Up to date';
                            row_data['.action'] = '<a href="#">Suggest correction</a>'; 
                            break;
                        case 'vulnerable':
                            row_data['.status'] = '<a href="'+data.url+'">Vulnerable</a>';
                            row_data['.action'] = '<a href="#">Suggest correction</a>'; 
                            break;
                        case 'outdated':
                            row_data['.status'] = '<a href="'+data.url+'">Needs update</a>'; 
                            row_data['.action'] = '<a href="#">Suggest correction</a>'; 
                            break;
                        default:
                            row_data['.status'] = 'Unknown'; 
                            row_data['.action'] = '<a href="#">Contribute info</a>'; 
                            break;
                    }

                    $this.appendTemplate(
                        plugins_table.find('tr.template'),
                        plugins_table, row_data
                    );
                    
                },
                function () {
                    console.log("FINISHED:");
                    console.dir(arguments);
                }
            );

        },

        /**
         * Append a clone of the given template to the parent, after filling in
         * with the given data.  This is done by matching each key of the template
         * data with CSS selectors on the template and using html() with the data.
         *
         * @param {DOMElement} tmpl   Template element
         * @param {DOMElement} parent Parent element
         * @param {Object}     data   Template data
         */
        appendTemplate: function (tmpl, parent, data) {
            var el = tmpl.clone().removeClass('template');
            for (k in data) if (data.hasOwnProperty(k)) {
                el.find(k).html(data[k]);
            }
            el.appendTo(parent);
        },

        EOF:null
    };

    return $this.init();

})();
