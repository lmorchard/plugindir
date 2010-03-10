/**
 * Main JS package for Plugin Directory
 */
/*jslint laxbreak: true */
PluginDir = (function () {

    var $ = window.$ = jQuery.noConflict();
    var $this = {

        pfs_endpoint: 'http://plugins.stage.mozilla.com/pfs/v2',

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

                // Append 'create sandbox plugin' link on sandbox tab with
                // detected browser info.
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

                // Cheap zebra striping for static tables
                $("tr:not(.template):nth-child(odd)").addClass("odd");
                $("tr:not(.template):nth-child(even)").addClass("even");

            });

            return this;
        },

        EOF:null
    };

    return $this.init();

})();
