/**
 * Main JS package for Plugin Directory
 */
/*jslint laxbreak: true */
PluginDir = (function () {

    var $this = {

        pfs_endpoint: 'http://dev.plugindir.mozilla.org/pfs/v2',

        /**
         * Initialize the package.
         */
        init: function () {

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
                $('.contribute select#status').each(function () {
                    var status = $(this),
                        fn = function () {
                            $('#vulnerability_url, #vulnerability_description')
                                .parent()
                                .toggle('vulnerable' == status.val());
                        };
                    fn();
                    status.change(fn);
                });

                $('.listing a.new_plugin').each(function () {
                    var submit_params = $.param(Pfs.UI.browserInfo());
                    $(this).attr('href', $(this).attr('href') + '?' + submit_params);
                });

                // Set up toggle-all checkboxes
                $('form .toggle_all').each(function () {
                    var parent = $(this);
                    parent.find('.toggler').click(function () {
                        var toggler = $(this);
                        parent.find('.toggled')
                            .attr('checked', toggler.attr('checked'));
                    });
                });

                $("tr:not(.template):nth-child(odd)").addClass("odd");
                $("tr:not(.template):nth-child(even)").addClass("even");

            });

            return this;
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
            for (k in data) { if (data.hasOwnProperty(k)) {
                var val = data[k];
                if (null === val) {
                    continue;
                }
                if ('@' === k.substring(0,1)) {
                    el.attr(k.substring(1), val);
                } else {
                    if ('string' === typeof val) {
                        el.find(k).html(val);
                    } else if ('undefined' != typeof val.nodeType) {
                        el.find(k).empty().append(val);
                    }
                }
            }}
            if (parent) { el.appendTo(parent); }
            return el[0];
        },

        EOF:null
    };

    return $this.init();

})();
