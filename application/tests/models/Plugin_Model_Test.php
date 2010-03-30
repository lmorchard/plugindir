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

    public static $test_plugin = null;

    public static $test_plugin_data = array(
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

        $this->cache = Cache::instance();
        $this->cache->delete_all();

        self::$test_plugin = 
            ORM::factory('plugin')->import(self::$test_plugin_data);
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
     * Exercise searching plugins using detection type.
     */
    public function testVersionDetectionTypeCriteria()
    {
        // Create a plugin with some releases keyed on detection type.
        $new_data = array(
            'meta' => array(
                'pfs_id'   => 'bazfoo-player',
                'name'     => 'Bazfoo Player',
                'vendor'   => 'Bazfoo Corp',
                'filename' => 'bazfoo.plugin',
                'platform' => array(
                    "app_id" => "{8675309}"
                )
            ),
            'mimes' => array(
                'audio/x-bazfoo-player',
            ),
            'releases' => array(
                array(
                    'version' => '12.3.4.5',
                    'guid'    => 'bazfoo',
                    'os_name' => 'mac',
                    'detection_type' => 'version_available'
                ),
                array(
                    'version' => '12.4',
                    'guid'    => 'bazfoo',
                    'os_name' => 'mac',
                    'detection_type' => 'original',
                ),
                array(
                    'version' => '15.6',
                    'guid'    => 'bazfoo',
                    'os_name' => 'mac',
                    'detection_type' => 'whatever',
                ),
            )
        );
        ORM::factory('plugin')->import($new_data);

        // Establish some base criteria
        $criteria = array(
            'appID'        => '{8675309}',
            'mimetype'     => array(
                'audio/x-bazfoo-player',
            ),
            'appVersion'   => '2008052906',
            'appRelease'   => '3.5',
            'clientOS'     => 'Intel Mac OS X 10.6',
            'chromeLocale' => 'en-US'
        );

        // Don't specify a type, highest version wins
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('15.6', 
            $releases['latest']['detected_version']);

        // Look for the original type
        $criteria['detection'] = 'original';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('12.4', 
            $releases['latest']['detected_version']);

        // Look for the version_available type
        $criteria['detection'] = 'version_available';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('12.3.4.5', 
            $releases['latest']['detected_version']);
    }

    /**
     * Exercise PFS ID suggestion from existing plugins or derived value
     */
    public function testPfsIdSuggestion()
    {
        $new_data = array(
            array(
                'meta' => array(
                    'name'   => 'The NEW Bazfoo player',
                    'pfs_id' => 'bazfoo-player',
                    'vendor' => 'Bazfoo Corp',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'filename' => 'bazfoo.plugin',
                        'version' => '12.3.4.5',
                        'guid'    => 'bazfoo',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
            array(
                'meta' => array(
                    'pfs_id'   => 'quux-player',
                    'filename' => 'quux.plugin',
                    'vendor'   => 'Bazfoo Corp',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'name' => 'The Brand New QUUX!',
                        'version' => '8.6.7',
                        'guid'    => 'quux',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
            array(
                'meta' => array(
                    'pfs_id'   => 'xyzzy-player',
                    'filename' => 'xyzzy.plugin',
                    'vendor'   => 'Bazfoo Corp',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'name' => 'New-school Xyzzy Flavor',
                        'version' => '9.9.9',
                        'guid'    => 'xyzzy',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
        );

        foreach ($new_data as $data) {
            ORM::factory('plugin')->import($data);
        }

        $criteria = array(
            'mimetype' => array(
                'audio/x-bazfoo-player',
            ),
        );

        $criteria['name'] = 'Bazfoo player';
        $criteria['filename'] = 'bazfoo.plugin';
        $pfs_ids = ORM::factory('plugin')->suggestPfsId($criteria);
        $this->assertEquals('bazfoo-player', $pfs_ids[0]);

        $criteria['name'] = 'The Brand New QUUX!';
        $criteria['filename'] = 'quux-16-32-64.plugin';
        $pfs_ids = ORM::factory('plugin')->suggestPfsId($criteria);
        $this->assertEquals('quux-player', $pfs_ids[0]);

        $criteria['name'] = 'Xyzzy Wrangler';
        $criteria['filename'] = 'xyzzy.plugin';
        $pfs_ids = ORM::factory('plugin')->suggestPfsId($criteria);
        $this->assertEquals('xyzzy-player', $pfs_ids[0]);

        $criteria['name'] = 'Super New Thingy 1.2.3.4.5';
        $criteria['filename'] = 'super-new.plugin';
        $pfs_ids = ORM::factory('plugin')->suggestPfsId($criteria);
        $this->assertEquals('super-new-thingy', $pfs_ids[0]);

    }

    /**
     * Exercise the filename criteria in lookup
     */
    public function testFilenameCriteria()
    {
        $new_data = array(
            array(
                'meta' => array(
                    'pfs_id'   => 'bazfoo-player',
                    'filename' => 'bazfoo.plugin',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'version' => '12.3.4.5',
                        'guid'    => 'bazfoo',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
            array(
                'meta' => array(
                    'pfs_id'   => 'quux-player',
                    'filename' => 'quux.plugin',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'version' => '8.6.7',
                        'guid'    => 'quux',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
            array(
                'meta' => array(
                    'pfs_id'   => 'xyzzy-player',
                    'filename' => 'xyzzy.plugin',
                    'platform' => array(
                        "app_id" => "{8675309}"
                    )
                ),
                'mimes' => array(
                    'audio/x-bazfoo-player',
                ),
                'releases' => array(
                    array(
                        'version' => '9.9.9',
                        'guid'    => 'xyzzy',
                        'os_name' => 'mac',
                        'detection_type' => 'version_available'
                    ),
                )
            ),
        );

        foreach ($new_data as $data) {
            ORM::factory('plugin')->import($data);
        }

        // Establish some base criteria
        $criteria = array(
            'appID'        => '{8675309}',
            'mimetype'     => array(
                'audio/x-bazfoo-player',
            ),
            'appVersion'   => '2008052906',
            'appRelease'   => '3.5',
            'clientOS'     => 'Intel Mac OS X 10.6',
            'chromeLocale' => 'en-US'
        );

        $criteria['filename'] = 'xyzzy.plugin';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('xyzzy-player',
            $results[0]['releases']['latest']['pfs_id']);

        $criteria['filename'] = 'bazfoo.plugin';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('bazfoo-player',
            $results[0]['releases']['latest']['pfs_id']);

        $criteria['filename'] = 'quux.plugin';
        list($results, $result, $releases, $versions) = 
            $this->lookupAndExtract($criteria);
        $this->assertEquals('quux-player',
            $results[0]['releases']['latest']['pfs_id']);

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
        ORM::factory('plugin')->import($plugin_update, TRUE);

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
                ->find(self::$test_plugin_data['meta']['pfs_id']);
            
            $result_export = $plugin->export();

            $this->assertTrue(is_array($result_export),
                "Export should be an array.");
            $this->assertTrue(!empty($result_export['meta']['pfs_id']),
                "PFS ID should be non-empty");
            $this->assertEquals(
                $result_export['meta']['pfs_id'],
                self::$test_plugin_data['meta']['pfs_id'],
                "PFS ID of export should match test plugin"
            );
            $this->assertEquals(
                count($result_export['releases']), 
                count(self::$test_plugin_data['releases'])
            );

            /*
             * The test data ensures that each release has a unique GUID, which 
             * isn't a real-world scenario in most cases, but it should 
             * minimally ensure that the import/export machinery isn't munging 
             * things.  Threw installer_location in there too, just for kicks.
             */
            $release_sets = array(
                'expected' => array( 
                    'releases' => self::$test_plugin_data['releases'], 
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
     * Verify that the ACLs are working properly
     */
    public function testACL() {

        $acl = authprofiles::$acls;

        ORM::factory('profile')->delete_all();

        $admin_profile = ORM::factory('profile')->set(array( 
            'screen_name' => 'admin', 'role' => 'admin',
        ))->save();

        $editor_profile = ORM::factory('profile')->set(array( 
            'screen_name' => 'editor', 'role' => 'editor',
        ))->save();

        $member1_profile = ORM::factory('profile')->set(array( 
            'screen_name' => 'member1', 'role' => 'member',
        ))->save();

        $member2_profile = ORM::factory('profile')->set(array( 
            'screen_name' => 'member2', 'role' => 'member',
        ))->save();

        $member3_profile = ORM::factory('profile')->set(array( 
            'screen_name' => 'member3', 'role' => 'member',
        ))->save();

        $plugin = self::$test_plugin;

        $privs = array( 
            'view', 'edit', 'delete', 'copy', 'submit_plugin', 'deploy', 
            'request_deploy'
        );

        $this->checkACL("Public plugin", $privs, $acl, $plugin, array(
            array('guest',          true,  false, false, false, true, false, false,),
            array($member1_profile, true,  false, false, true,  true, false, true,),
            array($member2_profile, true,  false, false, true,  true, false, true,),
            array($editor_profile,  true,  false, false, true,  true, true,  true,),
            array($admin_profile,   true,  true,  true,  true,  true, true,  true,),
        ));

        $plugin->set(array(
            'sandbox_profile_id' => $member1_profile->id
        ))->save();

        $this->checkACL("member1 sandbox plugin", $privs, $acl, $plugin, array(
            array('guest',          false, false, false, false, true, false, false,),
            array($member1_profile, true,  true,  true,  true,  true, false, true,),
            array($member2_profile, false, false, false, true,  true, false, true,),
            array($editor_profile,  true,  true,  true,  true,  true, true,  true,),
            array($admin_profile,   true,  true,  true,  true,  true, true,  true,),
        ));

        $plugin->set(array(
            'sandbox_profile_id' => $member2_profile->id
        ))->save();

        $this->checkACL("member2 sandbox plugin", $privs, $acl, $plugin, array(
            array('guest',          false, false, false, false, true, false, false,),
            array($member1_profile, false, false, false, true,  true, false, true,),
            array($member2_profile, true,  true,  true,  true,  true, false, true,),
            array($editor_profile,  true,  true,  true,  true,  true, true,  true,),
            array($admin_profile,   true,  true,  true,  true,  true, true,  true,),
        ));

        // Add member1 and member3 profile as trusted.  Should be able to do 
        // this several times in a row without mishap.
        $plugin->add_trusted($member1_profile);
        $plugin->add_trusted($member1_profile);
        $plugin->add_trusted($member1_profile);
        $plugin->add_trusted($member1_profile);
        $this->assertTrue($plugin->trusts($member1_profile)); 

        $plugin->add_trusted($member3_profile);
        
        // Ensure that the list of trusted profiles works properly.
        $trusted_profiles = $plugin->list_trusted();
        $trusted_screen_names = array();
        foreach ($trusted_profiles as $profile) {
            $trusted_screen_names[] = $profile->screen_name;
        }
        sort($trusted_screen_names);
        $this->assertEquals(
            array('member1', 'member3'),
            $trusted_screen_names,
            'Should list appropriate trusted profile screen names'
        );

        // Ensure member1 and member3 can perform as trusted
        $trusted_privs = array( 'view', 'edit', 'deploy' );
        $this->checkACL("member2 sandbox plugin (trust)", 
            $trusted_privs, $acl, $plugin, array(
                array('guest',          false, false, false,),
                array($member1_profile, true,  true,  true),
                array($member2_profile, true,  true,  false),
                array($member3_profile, true,  true,  true),
                array($editor_profile,  true,  true,  true),
                array($admin_profile,   true,  true,  true),
            )
        );

        // Remove member1 profile as trusted. Shouldn't need to match the count 
        // of add attempts.
        $plugin->remove_trusted($member1_profile);
        $this->assertTrue(!($plugin->trusts($member1_profile))); 
        $plugin->remove_trusted($member1_profile);
        $this->assertTrue(!($plugin->trusts($member1_profile))); 

        $this->checkACL("member2 sandbox plugin (trust minus member1)", 
            $trusted_privs, $acl, $plugin, array(
                array('guest',          false, false, false,),
                array($member1_profile, false, false, false),
                array($member2_profile, true,  true,  false),
                array($member3_profile, true,  true,  true),
                array($editor_profile,  true,  true,  true),
                array($admin_profile,   true,  true,  true),
            )
        );

        // Make a sandbox copy of the plugin for member1
        $export = $plugin->export();
        $export['meta']['sandbox_profile_id'] = $member1_profile->id;
        $export['meta']['original_plugin_id'] = $plugin->id;
        $new_plugin = ORM::factory('plugin')->import($export);

        // Ensure member3 still has trusted access to this sandboxed $new_plugin, 
        // which shares a PFS ID with $plugin yet has a different DB ID.
        $this->checkACL("member1 sandbox plugin (trust minus member1)", 
            $trusted_privs, $acl, $new_plugin, array(
                array('guest',          false, false, false,),
                array($member1_profile, true,  true,  false),
                array($member2_profile, false, false, false),
                array($member3_profile, true,  true,  true),
                array($editor_profile,  true,  true,  true),
                array($admin_profile,   true,  true,  true),
            )
        );

        // Finally, remove member3 from trusted list and ensure access goes away.
        $plugin->remove_trusted($member3_profile);
        $this->assertTrue(!($plugin->trusts($member3_profile))); 

        $this->checkACL("member2 sandbox plugin (trust minus member1)", 
            $trusted_privs, $acl, $plugin, array(
                array('guest',          false, false, false,),
                array($member1_profile, false, false, false),
                array($member2_profile, true,  true,  false),
                array($member3_profile, false, false, false),
                array($editor_profile,  true,  true,  true),
                array($admin_profile,   true,  true,  true),
            )
        );

    }


    /**
     * Test a set of roles and privs against the ACL.
     */
    function checkACL($msg, $privs, $acl, $resource, $cases)
    {
        foreach ($cases as $case) {
            
            $role = array_shift($case);

            $role_name = is_string($role) ? 
                $role : $role->screen_name;
            
            foreach ($case as $idx=>$expected) {
                $priv = $privs[$idx];

                $this->assertEquals(
                    $expected, $acl->isAllowed($role, $resource, $priv),
                    $msg . ': ' . $role_name . ' should' .
                        ( $expected ? '' : ' not') . 
                        ' be allowed to '. $priv
                );

                $this->assertEquals(
                    $expected, $resource->is_allowed($role, $priv),
                    $msg . ': ' . $role_name . ' should' .
                        ( $expected ? '' : ' not') . 
                        ' be allowed to '. $priv
                );
                
                if (!is_string($role)) {
                    $this->assertEquals(
                        $expected, $role->is_allowed($resource, $priv),
                        $msg . ': ' . $role_name . ' should' .
                            ( $expected ? '' : ' not') . 
                            ' be allowed to '. $priv
                    );
                }

            }

        }
    }

    /**
     * Perform a PFS2 lookup and extract some frequently used info.
     */
    public function lookupAndExtract($criteria)
    {
        $results = $this->plugin_model->lookup($criteria);
        if (empty($results)) {
            return array( null, null, null, null );
        }
        $result = $results[0];

        $releases = $result['releases'];

        $versions = array($releases['latest']['version'] => $releases['latest']);
        foreach ($releases['others'] as $release) {
            $versions[$release['version']] = $release;
        }

        return array($results, $result, $releases, $versions);
    }

}
