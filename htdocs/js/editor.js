/**
 * Main package for plugin editor
 */
PluginDir.Editor = (function () {

    var $this = {

        // JSON to load, should be set before document ready.
        json_url: null, 
        // Description of plugin properties.
        plugin_properties: {}, 
        // Whether or not to autosave using idle timer.
        autosave: true, 
        // Idle timer used to save when user is inbetween edits.
        save_timer: null, 
        // Whether or not a save is in progress
        save_inprogress: false, 
        // Time in milliseconds of idle time before save.
        save_delay: 2 * 1000, 

        app_ids: {
            '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}': 'Firefox'
        },

        /**
         * Package initialization
         */
        init: function () {
            $(document).ready($this.ready);
            return $this = this;
        },

        /**
         * Page setup on DOM ready.
         */
        ready: function () {

            // Disable form submit for editor.
            $('#editor').submit(function () { return false; });

            // Wire up autosave on mime & alias changes.
            $('#mimes, #literal_aliases, #regex_aliases')
                .change($this.scheduleSavePlugin);

            // Wire up manual save button.
            $('button#save').click($this.savePlugin);

            // Wire up autosave toggle box.
            $('#autosave').change(function () {
                $this.autosave = !$this.autosave;
                $this.updateStatusMessage("Autosave " + 
                    (($this.autosave) ? 'enabled' : 'disabled'));
            });

            // Wire up a quick & dirty outline UI
            $('form').click(function (ev) {
                if ('legend' == ev.target.nodeName.toLowerCase()) {
                    $(ev.target).parent().toggleClass('closed');
                }
            });

            // If the server set a JSON URL for loading, do so.
            if ($this.json_url) {
                $this.loadPlugin($this.json_url);
            }

        },

        /**
         * Update the status message.
         */
        updateStatusMessage: function (msg) {
            $('#save_message').text(msg);
            window.status = msg;
        },

        /**
         * Load plugin JSON by URL
         */
        loadPlugin: function (plugin_url) {
            $this.json_url = plugin_url;
            $.getJSON($this.json_url, $this.updateFromDefinition);
        },

        /**
         * Save the current state of plugin edits.
         */
        savePlugin: function () {
            if ($this.save_inprogress) return;
            $this.save_inprogress = true;

            $this.updateStatusMessage("Saving at " + (new Date()));

            $this.updateDefinitionFromForm();

            var json = $this.buildJSON(),
                url = PluginDir.base_url + 'plugins/detail/' +
                    encodeURIComponent($this.definition.meta.pfs_id) + 
                    '.json';

            // Perform the actual save via POST.
            $.ajax({
                type: 'POST',
                url: url,
                contentType: 'application/json',
                data: json,
                success: function () {
                    $this.save_inprogress = false;
                    $this.updateStatusMessage("Last saved at " + (new Date()));
                },
                error: function () {
                    $this.save_inprogress = false;
                    // TODO: More descriptive explanation of save failure.
                    $this.updateStatusMessage("Save failed at " + (new Date()));
                }
            });

        },

        /**
         * Schedule a save of the plugin using an idle timer.
         */
        scheduleSavePlugin: function () {
            // Bail if autosave disabled.
            if (!$this.autosave) return;
            // Bail if a save is in progress.
            if ($this.save_inprogress) return;
            // If a save timer is already ticking, cancel it.
            if ($this.save_timer) {
                clearTimeout($this.save_timer);
            }
            // Start a save timer ticking.
            $this.save_timer = setTimeout(
                $this.savePlugin, $this.save_delay
            );
        },

        /**
         * Update the current definition from a JS data structure.
         */
        updateFromDefinition: function (definition) {
            $this.definition = definition;
            $this.pfs_id = definition.meta.pfs_id;

            for (name in $this.definition.meta) {
                if (!$this.definition.meta.hasOwnProperty(name)) continue;
                var value = $this.definition.meta[name];
                $('#meta-fields input[name='+name+']').val(value);
            }

            $('#mimes')
                .val($this.definition.mimes.join("\n"));
            $('#literal_aliases')
                .val($this.definition.aliases.literal.join("\n"));
            $('#regex_aliases')
                .val($this.definition.aliases.regex.join("\n"));

            $this.rebuildReleaseFieldsets();
            $this.updateReleaseFieldsets();

            $this.buildJSON();
            $this.updateStatusMessage("Loaded at " + (new Date()));
        },

        /**
         * Rebuild the definition structure from the form fields.
         * 
         * @TODO: Make this more incremental - ie. only update a single release
         * when that release is changed.
         */
        updateDefinitionFromForm: function () {

            $this.definition.meta = 
                $this.extractPropertyFields($('#meta-fields'));

            // Forcibly overwrite any edits to PFS ID with original value.
            $this.definition.meta.pfs_id = $this.pfs_id;
            
            $this.definition.mimes = (''+$('#mimes').val()).split("\n");
            
            $this.definition.aliases = {
                literal: (''+$('#literal_aliases').val()).split("\n"),
                regex: (''+$('#regex_aliases').val()).split("\n"),
            };

            $this.definition.releases = [];
            $('ul#releases li.release ul.fields').each(function () {
                var release = $this.extractPropertyFields($(this));
                $this.definition.releases.push(release);
            })

        },

        /**
         * Build property blocks for releases
         */
        rebuildReleaseFieldsets: function () {
            $this.buildPropertyFields($('#meta-fields'), true);

            var releases_parent = $('#releases').empty();
            $.each($this.definition.releases, function (idx, release_data) {
                var release = $('.templates .releases .release')
                        .clone().appendTo(releases_parent),
                    fields = release.find('.fields');
                $this.buildPropertyFields(fields);
            });
        },

        /**
         * Build property blocks for releases
         */
        updateReleaseFieldsets: function () {
            $this.updatePropertyFields($('#meta-fields'), $this.definition.meta);
            $this.hideEmptyProperties($('#meta-fields'));

            $('#releases .release').each(function (idx) {
                var release = $this.definition.releases[idx];
                var parent  = $(this);
                $this.updatePropertyFields(parent, release);
                $this.hideEmptyProperties(parent);
                $this.updateReleaseSummary(parent);
            });
        },

        /**
         * Update property form fields from data.
         */
        updatePropertyFields: function (parent, data) {
            for (name in $this.plugin_properties) {
                if (!$this.plugin_properties.hasOwnProperty(name)) continue;
                
                var prop = $this.plugin_properties[name],
                    val = '';

                // Allow one level of data structure depth, specified by
                // 'parent' in property definition. (eg. platform')
                if (prop.parent) {
                    if (data[prop.parent]) {
                        val = data[prop.parent][name];
                    }
                } else {
                    val = data[name];
                }

                parent.find('*[name='+name+']').val(val);
            }
        },

        /**
         * Extract field values from a set of property form fields.
         */
        extractPropertyFields: function (parent) {
            var fields = { };
            for (name in $this.plugin_properties) {
                if (!$this.plugin_properties.hasOwnProperty(name)) continue;
                
                var prop = $this.plugin_properties[name],
                    val = parent.find('*[name='+name+']').val();

                if ('' === val || 'undefined' == val) continue;

                // Allow one level of data structure depth, specified by
                // 'parent' in property definition. (eg. platform')
                if (prop.parent) {
                    if (!fields[prop.parent]) {
                        fields[prop.parent] = {};
                    }
                    fields[prop.parent][name] = val;
                } else {
                    fields[name] = val;
                }
            }
            return fields;
        },

        /**
         * Encode the current plugin definition as JSON and shove it into a
         * data URL for local saving and a textarea for copying
         */
        buildJSON: function () {
            var json = JSON.stringify($this.definition, null, '    ');
            
            $('#json-out-text').val(json);
            $('#json-out-link').attr('href', 
                'data:application/json;charset=UTF-8,' + 
                encodeURIComponent(JSON.stringify($this.definition))
            );
            
            return json;
        },

        /**
         * Hide the fields with empty properties.
         */
        hideEmptyProperties: function (parent) {
            parent.find('.field').each(function () {
                var field = $(this),
                    input = field.find('input,textarea,select');
                if (!input.val() || field.hasClass('template')) { 
                    field.hide(); 
                } else {
                    field.show();
                }
            });
        },

        /**
         * Show all property fields
         */
        showAllProperties: function (parent) {
            parent.find('.field').each(function () {
                var field = $(this);
                if (field.hasClass('template')) { 
                    field.hide(); 
                } else {
                    field.show();
                }
            });
        },

        /**
         * Set up the property fields for a given field parent.
         */
        buildPropertyFields: function (parent, is_meta) {
            
            // Clean out the contents before rebuild.
            parent.empty();

            // Add and wire up the hide/show links for the property set.
            $('.templates .fields .controls')
                .clone().removeClass('template').appendTo(parent);
                
            parent.find('.controls .hide').click( function () {
                $this.hideEmptyProperties(parent); return false;
            });

            parent.find('.controls .show').click( function () {
                $this.showAllProperties(parent); return false;
            });

            for (name in $this.plugin_properties) {
                if (!$this.plugin_properties.hasOwnProperty(name)) continue;
                if (is_meta && 'status' === name) continue;
                //if (!is_meta && 'pfs_id' === name) continue;
                if ('pfs_id' === name) continue;

                var spec = $this.plugin_properties[name];

                $('.templates .fields .field.' + spec.type)
                    .clone().removeClass('template')
                    .find('label').text(name).end()
                    .find('input,textarea,select')
                        .attr('name', name)
                        .change($this.fieldChanged)
                    .end()
                    .find('p.notes').text(spec.description).end()
                    .appendTo(parent);
            }

            $this.hideEmptyProperties(parent);
        },

        /**
         * React to a changed field.
         */
        fieldChanged: function (ev) {
            $this.updateReleaseSummary($(this).parent().parent().parent().parent());
            $this.scheduleSavePlugin();
        },

        /**
         * Build a summary from some significant fields and update the legend
         * for the release.
         */
        updateReleaseSummary: function (release) {
            var parts = ['Release'],
                fields = [
                    'version', 'os_name', 'locale', 'app_id', 
                    'app_release', 'app_version'
                ];
            $.each(fields, function () {
                var name = this,
                    val = release.find('*[name='+name+']').val();
                console.log("NAME " + name);
                if ('app_id' == name && $this.app_ids[val]) { 
                    val = $this.app_ids[val];
                }
                if ('*' !== val) parts.push(val);
            });
            release.find('legend').text(parts.join(' - '));
        },

        EOF: null // I hate trailing comma errors
    };

    return $this.init();
}());
