/**
 * Utilities for PluginDir
 */
/*jslint laxbreak: true */
PluginDir.Utils = (function () {

    var $ = jQuery.noConflict();
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

        EOF: null
    };

    return $this.init();

}());
