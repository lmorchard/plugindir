/**
 * Main package for plugin editor
 */
PluginDir.Editor = (function () {

    // Static local package reference
    var $this = null;

    return {

        // JSON to load, should be set before document ready.
        json_url: null,
        plugin_properties: {},

        /**
         * Package initialization
         */
        init: function () {

            // Set up the page on document ready.
            $(document).ready(function () {

                $('#editor').submit(function () { return false; });

                if ($this.json_url) {
                    $this.loadPlugin($this.json_url);
                }

            });

            return $this = this;
        },

        /**
         * Load plugin JSON by URL
         */
        loadPlugin: function (plugin_url) {
            $.getJSON(plugin_url, $this.updateFromDefinition);
        },

        /**
         * Update the current definition from a JS data structure.
         */
        updateFromDefinition: function (definition) {
            $this.definition = definition;

            for (name in $this.definition.meta) {
                if (!$this.definition.meta.hasOwnProperty(name)) continue;
                var value = $this.definition.meta[name];

                $('#meta-fields input[name='+name+']').val(value);
            }

            $this.rebuildReleases();
            $this.updateReleases();
            $this.updateJSON();
        },

        /**
         * Build property blocks for releases
         */
        rebuildReleases: function () {
            $this.buildPropertyFields($('#meta-fields'));

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
        updateReleases: function () {
            $this.updatePropertyFields($('#meta-fields'), $this.definition.meta);
            $this.hideEmptyProperties($('#meta-fields'));

            $('#releases .release').each(function (idx) {
                var release = $this.definition.releases[idx];
                var parent  = $(this);
                $this.updatePropertyFields(parent, release);
                $this.hideEmptyProperties(parent);
            });
        },

        /**
         *
         */
        updatePropertyFields: function (parent, data) {
            for (name in $this.plugin_properties) {
                if (!$this.plugin_properties.hasOwnProperty(name)) continue;
                parent.find('*[name='+name+']').val(data[name]);
            }
        },

        /**
         * Encode the current plugin definition as JSON and shove it into a
         * data URL for local saving and a textarea for copying
         */
        updateJSON: function () {
            var json = JSON.stringify($this.definition, null, '    ');
            $('#json-out-text').val(json);
            $('#json-out-link').attr('href', 
                'data:application/json;charset=UTF-8,' + 
                encodeURIComponent(JSON.stringify($this.definition))
            );
        },

        /**
         * Hide the fields with empty properties.
         */
        hideEmptyProperties: function (parent) {
            parent.find('.field').each(function () {
                var field = $(this),
                    input = field.find('input');
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
        buildPropertyFields: function (parent) {
            
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

                var spec = $this.plugin_properties[name];

                $('.templates .fields .field.' + spec.type)
                    .clone().removeClass('template')
                    .find('label').text(name).end()
                    .find('input')
                        .attr('name', name)
                        .change($this.updateJSON)
                    .end()
                    .find('p.notes').text(spec.description).end()
                    .appendTo(parent);
            }

            $this.hideEmptyProperties(parent);
        },

        EOF: null // I hate trailing comma errors
    };

}()).init();
