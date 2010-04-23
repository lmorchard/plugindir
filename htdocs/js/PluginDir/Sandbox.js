/**
 * JS enhancements for the sandbox page template
 */
/*jslint laxbreak: true */
PluginDir.Sandbox = (function () {
    var $ = jQuery.noConflict();
    var $this = {

        screen_name: '',
        sandbox_plugins: {},
        not_in_sandbox: [],

        /**
         * Package initialization.
         */
        init: function () {
            $(document).ready($this.onReady);
            return this;
        },

        /**
         * Page ready handler.
         */
        onReady: function () {
            $this.performPluginDetection();
        },

        /**
         * Fire up the plugin detection engine.
         */
        performPluginDetection: function () {

            $this.not_in_sandbox  = [];
            $this.sandbox_plugins = {};

            $('.detected_in_sandbox tr.plugin').each(function () {
                var tr = $(this),
                    pfs_id = tr.attr('data-pfsid');
                $this.sandbox_plugins[pfs_id] = tr;
            });

            Pfs.endpoint = PluginDir.pfs_endpoint;

            var pluginsObject   = window.iePlugins || window.navigator.plugins || {},
                browser_plugins = Pfs.UI.browserPlugins(pluginsObject),
                browser_info    = Pfs.UI.browserInfo();

            browser_info.sandboxScreenName = $this.screen_name;

            Pfs.findPluginInfos(
                browser_info, 
                browser_plugins, 
                $this.handleDetectedPlugin,
                $this.handleUnknownPlugins
            );

        },

        /**
         * Process a detected plugin.
         */
        handleDetectedPlugin: function (data) {

            var has_pfs_match = (data.status !== 'unknown') && data.pfsInfo;
            var latest = (!has_pfs_match) ? null : data.pfsInfo.releases.latest;
            var pfs_id = (!has_pfs_match) ? null : latest.pfs_id;

            if (!pfs_id || !$this.sandbox_plugins[pfs_id]) {
                return $this.not_in_sandbox.push(data);
            }

            var version = data.pluginInfo.detected_version || 
                Pfs.parseVersion(data.pluginInfo.plugin).join('.');

            $this.sandbox_plugins[pfs_id]
                .removeClass('inprogress').addClass('detected')
                .find('.detected').text(version).end()
                .find('.pfs_status').text(latest.status).end()
                ;
        },

        /**
         * Handle all the unknown plugins after primary detection done.
         */
        handleUnknownPlugins: function () {

            // Mark any sandbox plugins still in progress as failed matches.
            $('.detected_in_sandbox tr.inprogress').each(function () {
                $(this).each(function () {
                    $(this)
                        .removeClass('inprogress').addClass('undetected')
                        .find('.detected').text('').end()
                        .find('.pfs_status').text('unknown').end()
                        ;
                });
            });

            // Process any plugins whose versions couldn't be detected.
            $.each(Pfs.UI.unknownVersionPlugins, function () {
                var plugin = this;
                $('.unknown .template')
                    .cloneTemplate({
                        '.name': plugin.name,
                        '.description': plugin.description
                    })
                    .appendTo('.unknown .plugins');
            });

            // Process any detected plugins not in the sandbox.
            $.each($this.not_in_sandbox, function () {

                var data = this;
                var raw_plugin = data.pluginInfo.raw;
                var version = data.pluginInfo.detected_version || 
                        Pfs.parseVersion(data.pluginInfo.plugin).join('.');
                var has_pfs_match = (data.status !== 'unknown') && data.pfsInfo;
                var latest = (!has_pfs_match) ? null : data.pfsInfo.releases.latest;
                var pfs_id = (!has_pfs_match) ? null : latest.pfs_id;

                // Build URL params for use in linking to the contribution
                // and creation forms, composed of detected plugin details
                // and browser info.
                var submit_params = $.param($.extend({
                    status: data.status,
                    pfs_id: pfs_id || '',
                    version: version,
                    detected_version: data.pluginInfo.detected_version,
                    detection_type: data.pluginInfo.detection_type,
                    name: raw_plugin.name,
                    filename: raw_plugin.filename,
                    description: raw_plugin.description,
                    vendor: (!has_pfs_match) ? '' : latest.vendor,
                    mimetypes: data.pluginInfo.mimes.join("\n")
                }, Pfs.UI.browserInfo()));

                var tmpl_data = {
                    '.name': data.pluginInfo.raw.name,
                    '.version': version
                };

                if (!pfs_id) {
                    var profile_url = PluginDir.base_url +
                        'profiles/' + $this.screen_name;
                    $('.not_found .template')
                        .cloneTemplate($.extend(tmpl_data, {
                            '.create @href': 
                                profile_url + '/plugins;create?' + submit_params
                        }))
                        .appendTo('.not_found .plugins');
                } else {
                    var base_url = PluginDir.base_url + 'plugins/detail/' + pfs_id;
                    $('.found_outside .template')
                        .cloneTemplate($.extend(tmpl_data, {
                            '.detail @href': base_url + '#' + version,
                            '.copy @href': base_url + ';copy',
                            '.pfs_id': pfs_id,
                            '.status': latest.status
                        }))
                        .appendTo('.found_outside .plugins');
                }
            });
            
            // Reveal hidden sections with items added.
            $(".sandbox_sections > div").each(function() {
                var sect = $(this),
                    items = sect.find('.plugin:not(.template)');
                if (items.length) sect.show();
            });

        },

        EOF:null
    };

    return $this.init();

})();
