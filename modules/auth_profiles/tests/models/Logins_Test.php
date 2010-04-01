<?php
/**
 * Test class for Model_User.
 *
 * @package    auth_profiles
 * @subpackage tests
 * @author     l.m.orchard <l.m.orchard@pobox.com>
 * @group      auth_profiles
 * @group      models
 * @group      models.auth_profiles
 * @group      models.auth_profiles.logins
 */
class Logins_Test extends PHPUnit_Framework_TestCase 
{
    /**
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        LMO_Utils_EnvConfig::apply('testing');

        $this->login_model = new Login_Model();
        $this->login_model->delete_all();

        $this->profile_model = new Profile_Model();
        $this->profile_model->delete_all();
    }

    /**
     * Ensure that required fields for a login are enforced.
     */
    public function pass_testCreateRequiredFields()
    {
        try {
            $test_id = $this->logins_model->create(array());
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e1) {
            $this->assertContains('required', $e1->getMessage());
        }
        try {
            $test_id = $this->logins_model->create(array(
                'login_name' => 'tester1'
            ));
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e2) {
            $this->assertContains('required', $e2->getMessage());
        }
        try {
            $test_id = $this->logins_model->create(array(
                'login_name' => 'tester1',
                'password'   => 'tester_password'
            ));
            $this->fail('Logins with missing fields should not be allowed');
        } catch (Exception $e3) {
            $this->assertContains('required', $e3->getMessage());
        }
        try {
            $test_id = $this->logins_model->create(array(
                'login_name' => 'tester1',
                'password'   => 'tester_password',
                'email'      => 'tester1@example.com'
            ));
        } catch (Exception $e) {
            $this->fail('Users with duplicate login names should raise exceptions');
        }
    }

    /**
     * Ensure a login can be created and found by login name.
     */
    public function test_create_and_fetch_login()
    {
        ORM::factory('login')->set(array(
            'login_name' => 'tester1',
            'email'      => 'tester1@example.com',
            //'password'   => 'tester_password',
        ))->save();

        $login = ORM::factory('login', 'tester1');

        $this->assertEquals($login->login_name, 'tester1');
        $this->assertEquals($login->email, 'tester1@example.com');
    }

    /**
     * Ensure that logins with the same login names cannot be created.
     */
    public function test_should_not_allow_duplicate_login_name()
    {
        ORM::factory('login')->set(array(
            'login_name' => 'tester1',
            'email'      => 'tester1@example.com',
            'password'   => 'tester_password',
        ))->save();

        try {
            ORM::factory('login')->set(array(
                'login_name' => 'tester1',
                'email'      => 'tester1@example.com',
                'password'   => 'tester_password'
            ))->save();

            $this->fail('Users with duplicate login names should raise exceptions');
        } catch (Exception $e) {
            $this->assertContains('Duplicate', $e->getMessage());
        }
    }

    /**
     * Exercise password hashing and respective algos
     */
    public function test_password_hashing()
    {
        $login_model = ORM::factory('login'); 

        ORM::factory('login')->set(array(
            'login_name' => 'tester42',
            'email'      => 'tester1@example.com',
            'password'   => 'tester_password',
        ))->save();

        list($algo, $salt, $hash) = $login_model
            ->parse_password_hash('528fbfb3293b1e0be03766af476e0117');
        $this->assertEquals('MD5', $algo,
            'Legacy hash should yield MD5 algo');
        $this->assertEquals(null, $salt,
            'Legacy hash should yield null salt');
        $this->assertEquals('528fbfb3293b1e0be03766af476e0117', $hash,
            'Legacy hash should yield self as hash');

        $passwords = array( 'trustno1', 'n0mor3s3cr37$', 'i like pie' );
        foreach ($passwords as $password) {

            $full_hash = $login_model->hash_password($password);
            list($algo, $salt, $hash) = 
                $login_model->parse_password_hash($full_hash);

            $this->assertEquals('SHA-256', $algo,
                'Default algo should be SHA-256');
            $this->assertTrue( (null !== $salt),
                'Default algo hash should yield a salt.');
            $this->assertTrue( ($hash != $full_hash),
                'Default algo hash should not equal input str');

            $this->assertTrue(
                $login_model->check_password($password, $full_hash),
                'Password check should be true.'
            );
            $this->assertTrue(
                !$login_model->check_password('incorrect'.$password, $full_hash),
                'Bad password check should be false'
            );

            $login = ORM::factory('login', 'tester42');
            $login->change_password($password);

            $login_data_1 = array(
                'login_name' => $login->login_name,
                'password'   => $password
            );
            $is_valid = $login_model->validate_login($login_data_1);
            $this->assertTrue($is_valid, 'Valid login should be valid.');

            $login_data_2 = array(
                'login_name' => $login->login_name,
                'password'   => 'incorrect-'.$password
            );
            $is_valid = $login_model->validate_login($login_data_2);
            $this->assertTrue(!$is_valid, 'Invalid login should be invalid.');

        }

    }

    /**
     *
     */
    public function test_legacy_md5_password_migration()
    {
        $db = Database::instance( Kohana::config('model.database') );

        $login_model = ORM::factory('login'); 

        ORM::factory('login')->set(array(
            'login_name' => 'tester42',
            'email'      => 'tester1@example.com'
        ))->save();

        $login = ORM::factory('login', 'tester42');

        $password = 'n0mor3s3cr37$';

        // Forcibly set the login password to an old-style MD5 hash
        $md5_hash = md5($password);
        $db->update(
            'logins', 
            array('password'=>$md5_hash),
            array('id'=>$login->id)
        );

        $login_data_1 = array(
            'login_name' => $login->login_name,
            'password'   => $password
        );
        $is_valid = $login_model->validate_login($login_data_1);
        $this->assertTrue($is_valid, 'Valid login should be valid.');

        $row = $db
            ->select('password')
            ->from('logins')
            ->where('login_name', $login->login_name)
            ->get()->current();

        $this->assertTrue(
            ($row->password != $md5_hash),
            "MD5 hash should have been migrated to new-style"
        );

        list($algo, $salt, $hash) = 
            $login_model->parse_password_hash($row->password);

        $this->assertEquals('SHA-256', $algo,
            'Default algo should be SHA-256');
        $this->assertTrue( (null !== $salt),
            'Default algo hash should yield a salt.');
        $this->assertTrue( ($hash != $row->password),
            'Default algo hash should not equal input str');

        $login_data_2 = array(
            'login_name' => $login->login_name,
            'password'   => $password
        );
        $is_valid = $login_model->validate_login($login_data_2);
        $this->assertTrue($is_valid, 
            'Valid login should be valid, after migration.');

    }
    
}
