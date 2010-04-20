#!/usr/bin/env python
"""
Simple tool to export all plugins from a plugin directory install into local
JSON files.
"""
import sys, os, os.path, json, urllib2, base64
from optparse import OptionParser

def main():
    (options, args) = parse_opts()

    if options.username and options.password:
        setup_basic_auth(options, options.index_url)

    print "Fetching index..."
    index_json = json.loads(urllib2.urlopen(options.index_url).read())
    print "\tfound %s plugins." % (len(index_json))
    for details in index_json:

        print "Fetching %s (%s)..." % (details['name'], details['pfs_id'])
        setup_basic_auth(options, details['href'])
        plugin = json.loads(urllib2.urlopen(details['href']).read())

        out_fn = os.path.join(options.output_dir, 
                "%s.json" % plugin['meta']['pfs_id'])
        
        if (os.path.exists(out_fn)):
            print "\trenaming existing %s" % (out_fn)
            os.rename(out_fn, "%s-bak" % out_fn)
        
        print "\tsaving %s" % (out_fn)
        json.dump(plugin, open(out_fn, 'w'), indent=4)


def parse_opts():
    """Parse the command line options"""
    op = OptionParser()
    op.add_option("-i", "--index", dest="index_url",
            help="URL for plugin directory JSON index", 
            default="http://dev.plugindir.mozilla.org/en-us/index.json")
    op.add_option("-o", "--output", dest="output_dir",
            help="Output directory for JSON files",
            default="plugins-info")
    op.add_option("-u", "--user", dest="username",
            help="HTTP basic auth user name")
    op.add_option("-p", "--password", dest="password",
            help="HTTP basic auth password")
    return op.parse_args()

def setup_basic_auth(options, url):
    """Set up HTTP Basic auth from command line options"""
    pass_mgr = urllib2.HTTPPasswordMgrWithDefaultRealm()
    pass_mgr.add_password(None, url, options.username, options.password)
    auth_handler = urllib2.HTTPBasicAuthHandler(pass_mgr)
    opener = urllib2.build_opener(auth_handler)
    urllib2.install_opener(opener)

if __name__ == "__main__":
    sys.exit(main())
