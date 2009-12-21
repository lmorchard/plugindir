<?php
/**
 * Tests for Plugin_Model
 *
 * @package    PluginDir
 * @subpackage tests
 * @author     lorchard@mozilla.com
 * @group      models
 * @group      models.plugindir
 * @group      models.plugindir.plugin
 */
class Plugin_Model_Test extends PHPUnit_Framework_TestCase
{
    // {{{ Test data

    public static $test_plugin = array(
        'meta' => array(
            'pfs_id'   => 'foobar-media',
            'name'     => 'Foobar Media Viewer',
            'vendor'   => 'Foobar',
            'filename' => 'foobar.plugin',
            'platform' => array(
                "app_id" => "{ec8030f7-c20a-464f-9b0e-13a3a9e97384}"
            )
        ),
        'aliases' => array(
            'literal' => array(
                'Super Happy Future Viewer',
            ),
            'regex' => array(
                'Foobar Corporation Viewer of Media.*',
                'Barcorp.*Moving Picture Thingy.*'
            ),
        ),
        'mimes' => array(
            'audio/x-foobar-audio',
            'video/x-foobar-video'
        ),
        'releases' => array(
            array(
                'version' => '100.2.6',
                'guid'    => 'foobar-win-100.2.6',
                'os_name' => 'win',
                'installer_location' => 'http://example.com/foobar/win.exe',
            ),
            array(
                'version' => '100.2.6',
                'guid'    => 'foobar-mac-100.2.6',
                'os_name' => 'mac',
                'installer_location' => 'http://example.com/foobar/mac.dmg',
            ),
            array(
                'version' => '100.2.6',
                'guid'    => 'foobar-other-100.2.6',
                'installer_location' => 'http://example.com/foobar/others.zip',
            ),
            array(
                'version' => '99.9.9',
                'name'    => 'Horribly Broken Media Viewer',
                'guid'    => 'foobar-bad-99.9.9',
                'status'  => 'vulnerable',
                'vulnerability_description' => 'Kicks puppies',
                'vulnerability_url' => 'http://example.com/foobar/oops.html',
                'installer_location' => 'http://example.com/foobar/oops.zip',
            ),
            array(
                'version' => '100.2.6',
                'guid' => 'foobar-mac-ja_JP-100.2.6',
                'os_name' => 'mac',
                'platform' => array(
                    'app_id' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
                    'locale' => 'ja-JP',
                ),
                'installer_location' => 'http://example.com/foobar/mac-ja-JP.dmg'
            ),
        )
    );

    // }}}

    /**
     * Set up for each test.
     */
    public function setUp()
    {
        LMO_Utils_EnvConfig::apply('testing');

        // Clear out all model data, wipe caches, and stash instances.
        $models = array(
            'mimetype', 'os', 'platform', 'pluginalias', 'pluginrelease', 
            'submission', 'plugin'
        );
        foreach ($models as $model) {
            $this->{"{$model}_model"} = ORM::factory($model)
                ->delete_all()
                ->clear_cache();
        }

        ORM::factory('plugin')->import(self::$test_plugin);
    }

    /**
     * Perform some simple searches against mime-types and platforms.
     */
    public function testMimeTypeAndOsCriteriaShouldYieldCorrectPlugins()
    {

        // No plugins defined for this app or OS, so results should be empty.
        $results = $this->plugin_model->lookup(array(
            'appID'    => '{abcdef123456789}',
            'mimetype' => 'audio/x-foobar-audio',
            'clientOS' => 'ReactOS 23.42',
            'appVersion'   => '20090810',
            'appRelease'   => '3.5',
            'chromeLocale' => 'en-US'
        ));
        $this->assertTrue(empty($results),
            "There should be no plugin for clientOS ReactOS 23.42");

        // No plugins defined for this mimetype
        $results = $this->plugin_model->lookup(array(
            'appID'    => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
            'mimetype' => 'application/x-xyzzy-animation',
            'clientOS' => 'win',
            'appVersion'   => '20090810',
            'appRelease'   => '3.5',
            'chromeLocale' => 'en-US'
        ));
        $this->assertTrue(empty($results),
            "There should be no plugin for application/x-xyzzy-animation");

        // Try some criteria for which results are expected:
        $criteria = array(
            'appID'        => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
            'mimetype'     => array(
                'audio/x-foobar-audio',
                'video/x-foobar-video'
            ),
            'appVersion'   => '2008052906',
            'appRelease'   => '3.0',
            'clientOS'     => 'Windows NT 5.1',
            'chromeLocale' => 'en-US'
        );

        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);

        // Assert that the plugin has expected aliases
        $this->assertTrue( !empty($result['aliases']),
            'Plugin should provide aliases');
        $this->assertTrue( !empty($result['aliases']['literal']),
            'Plugin should provide literal aliases');
        $this->assertTrue( !empty($result['aliases']['regex']),
            'Plugin should provide regex aliases');

        $expected = array(
            'literal' => array(
                'Foobar Media Viewer',
                'Horribly Broken Media Viewer',
                'Super Happy Future Viewer',
            ),
            'regex' => array(
                'Foobar Corporation Viewer of Media.*',
                'Barcorp.*Moving Picture Thingy.*'
            ),
        );
        foreach ($expected as $kind=>$e_data) {
            sort($e_data);
            sort($result['aliases'][$kind]);
            $this->assertEquals($e_data, $result['aliases'][$kind]);
        }

        // There should be non-empty releases
        $this->assertTrue( !empty($result['releases']),
           "Releases should be non-empty" );
        $this->assertTrue( !empty($releases['latest']),
           "Latest release should be present." );
        $this->assertTrue( !empty($releases['others']),
           "Other releases should be present." );

        // Assert the versions expected from test data
        $expected_versions = array( '100.2.6', '99.9.9' );
        $this->assertEquals( $expected_versions, array_keys($versions) );

        // Assert the latest version
        $this->assertEquals('100.2.6', $releases['latest']['version'],
            'Latest version should be present and match.');

        // Verify the GUIDs and statuses
        $this->assertEquals('foobar-win-100.2.6', 
            $versions['100.2.6']['guid']);
        $this->assertEquals('latest', 
            $versions['100.2.6']['status']);
        $this->assertEquals('foobar-bad-99.9.9', 
            $versions['99.9.9']['guid']);
        $this->assertEquals('vulnerable', 
            $versions['99.9.9']['status']);

        $this->assertEquals('Foobar Media Viewer',
            $versions['100.2.6']['name']);
        $this->assertEquals('Horribly Broken Media Viewer',
            $versions['99.9.9']['name']);

        // Now, switch to Mac and expect one of the plugins to change.
        $criteria['clientOS'] = 'Intel Mac OS X 10.5';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);

        $this->assertTrue( !empty($result),
            'Plugin of pfs_id "foobar-media" should be returned');
        $this->assertTrue( !empty($versions['99.9.9']),
            'Release v99.9.9 of "foobar-media" should be returned');
        $this->assertTrue( !empty($versions['100.2.6']),
            'Release v100.2.6 of "foobar-media" should be returned');

        $this->assertEquals('foobar-mac-100.2.6', 
            $versions['100.2.6']['guid']);
        $this->assertEquals('Foobar Media Viewer',
            $versions['100.2.6']['name']);

        $this->assertEquals('foobar-bad-99.9.9', 
            $versions['99.9.9']['guid']);
        $this->assertEquals('Horribly Broken Media Viewer',
            $versions['99.9.9']['name']);

        // Now get specific with locale and expect the Mac release to change again.
        $criteria['chromeLocale'] = 'ja-JP';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);

        $this->assertEquals('foobar-mac-ja_JP-100.2.6', 
            $versions['100.2.6']['guid']);
        $this->assertEquals('foobar-bad-99.9.9', 
            $versions['99.9.9']['guid']);

    }

    /**
     * Try updating without deleting
     */
    public function testLaterUpdatesShouldWork()
    {
        $plugin_update = array(
            'meta' => array(
                'pfs_id'   => 'foobar-media',
                'name'     => 'Foobar Media Viewer',
                'vendor'   => 'Foobar',
                'filename' => 'foobar.plugin',
                'platform' => array(
                    "app_id" => "{ec8030f7-c20a-464f-9b0e-13a3a9e97384}"
                )
            ),
            'aliases' => array(
                'Foobar Corporation Viewer of Media',
            ),
            'mimes' => array(
                'audio/x-foobar-audio',
                'new-video/x-foobar-new-video'
            ),
            'releases' => array(
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-win-200.9.9',
                    'os_name' => 'win',
                    'installer_location' => 'http://example.com/foobar/win-200.exe',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-mac-200.9.9',
                    'os_name' => 'mac',
                    'installer_location' => 'http://example.com/foobar/mac.dmg',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-other-200.9.9',
                    'installer_location' => 'http://example.com/foobar/others.zip',
                ),
                array(
                    'version' => '100.2.6',
                    'status'  => 'outdated',
                    'guid'    => 'foobar-win-100.2.6',
                    'os_name' => 'win',
                    'installer_location' => 'http://example.com/foobar/win.exe',
                ),
                array(
                    'version' => '100.2.6',
                    'status'  => 'outdated',
                    'guid'    => 'foobar-mac-100.2.6',
                    'os_name' => 'mac',
                    'installer_location' => 'http://example.com/foobar/mac.dmg',
                ),
                array(
                    'version' => '100.2.6',
                    'status'  => 'outdated',
                    'guid'    => 'foobar-other-100.2.6',
                    'installer_location' => 'http://example.com/foobar/others.zip',
                ),
                array(
                    'version' => '99.9.9',
                    'status'  => 'outdated',
                    'name'    => 'Horribly Broken Media Viewer',
                    'guid'    => 'foobar-bad-99.9.9',
                    'status'  => 'vulnerable',
                    'vulnerability_description' => 'Kicks puppies',
                    'vulnerability_url' => 'http://example.com/foobar/oops.html',
                ),
                array(
                    'version' => '100.2.6',
                    'status'  => 'outdated',
                    'guid' => 'foobar-mac-ja_JP-100.2.6',
                    'os_name' => 'mac',
                    'platform' => array(
                        'app_id' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
                        'locale' => 'ja-JP',
                    ),
                    'installer_location' => 'http://example.com/foobar/mac-ja-JP.dmg'
                ),
            )
        );
        ORM::factory('plugin')->import($plugin_update);

        foreach (array( 'Windows NT 5.1', 'Intel Mac OS X 10.5', 'React OS' ) as $os_name ) {

            // Try some criteria for which results are expected:
            $criteria = array(
                'appID'        => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
                'mimetype'     => array(
                    'audio/x-foobar-audio',
                    'video/x-foobar-video'
                ),
                'appVersion'   => '2008052906',
                'appRelease'   => '3.5',
                'clientOS'     => $os_name,
                'chromeLocale' => 'en-US'
            );

            list($results, $result, $releases, $versions) = 
                $this->lookupAndExtract($criteria);

            switch ($os_name) {
                case 'Windows NT 5.1':
                    $expected_installer = 'http://example.com/foobar/win-200.exe';
                    break;
                case 'Intel Mac OS X 10.5':
                    $expected_installer = 'http://example.com/foobar/mac.dmg';
                    break;
                case 'React OS':
                    $expected_installer = 'http://example.com/foobar/others.zip';
                    break;
            }
            $this->assertEquals($expected_installer,
                $versions['200.9.9']['installer_location']);

            $this->assertEquals('200.9.9', $releases['latest']['version'],
                'Latest version should be present and match.');

            $this->assertTrue( !empty($versions['200.9.9']),
                'Release v200.9.9 of "foobar-media" should be returned');
            $this->assertEquals('latest', $versions['200.9.9']['status']);

            $this->assertTrue( !empty($versions['100.2.6']),
                'Release v100.2.6 of "foobar-media" should be returned');
            $this->assertEquals('outdated', $versions['100.2.6']['status']);
            
            $this->assertTrue( !empty($versions['99.9.9']),
                'Release v99.9.9 of "foobar-media" should be returned');
            $this->assertEquals('vulnerable', $versions['99.9.9']['status']);

            // Ensure that the missing alias no longer is associated with the 
            // plugin.
            $plugin = ORM::factory('plugin', 'foobar-media');
            foreach ($plugin->pluginaliases as $alias) {
                $this->assertTrue(
                    $alias->alias != 'Super Happy Future Viewer',
                    "There should no longer be a Super Happy Future Viewer alias"
                );
            } 
            foreach ($plugin->mimetypes as $mimetype) {
                $this->assertTrue(
                    $mimetype->name != 'video/x-foobar-video',
                    "There should no longer be a 'video/x-foobar-video' mimetype"
                );
            }

        }
    }

    /**
     * Exercise relevant matches on exact and fuzzy OS name matches
     */
    public function testOSRelevance()
    {
        $plugin_update = array(
            'meta' => array(
                'pfs_id'   => 'foobar-media',
                'name'     => 'Foobar Media Viewer',
                'vendor'   => 'Foobar',
                'filename' => 'foobar.plugin',
                'platform' => array(
                    "app_id" => "{ec8030f7-c20a-464f-9b0e-13a3a9e97384}"
                )
            ),
            'aliases' => array(
                'Super Happy Future Viewer',
                'Foobar Corporation Viewer of Media',
            ),
            'mimes' => array(
                'audio/x-foobar-audio',
                'video/x-foobar-video'
            ),
            'releases' => array(
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-win-200.9.9',
                    'os_name' => 'win',
                    'installer_location' => 'http://example.com/foobar/win-200.exe',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-win-vista-200.9.9',
                    'os_name' => 'windows vista',
                    'installer_location' => 'http://example.com/foobar/win-200.exe',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-intel-mac-os-x-10.6-200.9.9',
                    'os_name' => 'intel mac os x 10.6',
                    'installer_location' => 'http://example.com/foobar/mac.dmg',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-intel-mac-os-x-10.5-200.9.9',
                    'os_name' => 'intel mac os x 10.5',
                    'installer_location' => 'http://example.com/foobar/mac.dmg',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-mac-200.9.9',
                    'os_name' => 'mac',
                    'installer_location' => 'http://example.com/foobar/mac.dmg',
                ),
                array(
                    'version' => '200.9.9',
                    'guid'    => 'foobar-other-200.9.9',
                    'installer_location' => 'http://example.com/foobar/others.zip',
                ),
            )
        );
        ORM::factory('plugin')->import($plugin_update);

        $os_guid_map = array(
            'Windows 98'          => 'foobar-win-200.9.9',
            'Windows NT 6.0'      => 'foobar-win-vista-200.9.9',
            'Intel Mac OS X 10.4' => 'foobar-mac-200.9.9', 
            'Intel Mac OS X 10.5' => 'foobar-intel-mac-os-x-10.5-200.9.9', 
            'Intel Mac OS X 10.6' => 'foobar-intel-mac-os-x-10.6-200.9.9',
            'React OS 11.92'      => 'foobar-other-200.9.9'
        );

        foreach ($os_guid_map as $os_name => $expected_guid) {

            // Try some criteria for which results are expected:
            $criteria = array(
                'appID'        => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
                'mimetype'     => array(
                    'audio/x-foobar-audio',
                    'video/x-foobar-video'
                ),
                'appVersion'   => '2008052906',
                'appRelease'   => '3.0',
                'clientOS'     => $os_name,
                'chromeLocale' => 'en-US'
            );

            list($results, $result, $releases, $versions) = 
                $this->lookupAndExtract($criteria);

            $this->assertEquals($expected_guid,
                $releases['latest']['guid']);

        }

    }

    /**
     * Exercise export / import functionality by repeatedly exporting and 
     * importing the export, comparing results against test data.
     */
    public function testExportImport()
    {
        foreach (array(1, 2, 3) as $attempt) {

            $plugin = ORM::factory('plugin')
                ->find(self::$test_plugin['meta']['pfs_id']);
            
            $result_export = $plugin->export();

            $this->assertTrue(is_array($result_export),
                "Export should be an array.");
            $this->assertTrue(!empty($result_export['meta']['pfs_id']),
                "PFS ID should be non-empty");
            $this->assertEquals(
                $result_export['meta']['pfs_id'],
                self::$test_plugin['meta']['pfs_id'],
                "PFS ID of export should match test plugin"
            );
            $this->assertEquals(
                count($result_export['releases']), 
                count(self::$test_plugin['releases'])
            );

            /*
             * The test data ensures that each release has a unique GUID, which 
             * isn't a real-world scenario in most cases, but it should 
             * minimally ensure that the import/export machinery isn't munging 
             * things.  Threw installer_location in there too, just for kicks.
             */
            $release_sets = array(
                'expected' => array( 
                    'releases' => self::$test_plugin['releases'], 
                    'sigs' => array() 
                ),
                'result'   => array( 
                    'releases' => $result_export['releases'], 
                    'sigs' => array() 
                )
            );
            foreach ($release_sets as $name => $stuff) {
                foreach ($stuff['releases'] as $release) {
                    $release_sets[$name]['sigs'][] = join('::', array( 
                        $release['version'], 
                        $release['guid'],
                        $release['installer_location']
                    ));
                }
                sort($release_sets[$name]['sigs']);
            }
            $this->assertEquals(
                $release_sets['expected']['sigs'],
                $release_sets['result']['sigs'],
                "Test and export record signature lists should match"
            );

            // TODO: Need more tests here?

            ORM::factory('plugin')->import($result_export);
        }

    }
    

    /**
     * Perform a PFS2 lookup and extract some frequently used info.
     */
    public function lookupAndExtract($criteria)
    {
        $results = $this->plugin_model->lookup($criteria);
        $result = $results[0];

        $releases = $result['releases'];

        $versions = array($releases['latest']['version'] => $releases['latest']);
        foreach ($releases['others'] as $release) {
            $versions[$release['version']] = $release;
        }

        return array($results, $result, $releases, $versions);
    }

}
