/**
 * Utilities for PluginDir
 */
/*jslint laxbreak: true */
PluginDir.Utils = (function () {

    var $this = {

        /**
         * Initialize the package.
         */
        init: function () {
            return this;
        },

        /**
         * Parse an object out of a URL query string
         */
        parseQueryString: function (qs) {
            if (!qs) { qs = location.href; }
            var pairs = qs.substr(qs.indexOf('?')+1).split('&'),
                out = {};
            for (var i=0; i<pairs.length; i++) {
                var pair = pairs[i].split('=');
                out[pair[0]] = decodeURIComponent(pair[1])
                    .replace(/\+/g, ' ');
            }
            return out;
        },

        EOF: null
    };

    return $this.init();

}());
