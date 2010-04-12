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
     * Exercise the transition from legacy MD5 password hashes to salted 
     * SHA-256 hashes
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
    
    /**
     * Exercise the email change verification tokens for new (change) and 
     * old (revert) email addresses.
     */
    public function test_old_and_new_email_verification_tokens()
    {
        $login_model = ORM::factory('login');

        ORM::factory('login')->set(array(
            'login_name' => 'tester42',
            'email'      => 'tester1@example.com',
            'password'   => 'tester_password',
        ))->save();

        $login = ORM::factory('login', 'tester42');

        $old_email = $login->email;
        $new_email = 'tester-1@newexample.com';

        $token_old = $login->generate_email_verification_token();
        $token_new = $login->generate_email_verification_token($new_email);

        $this->assertTrue($token_old != $token_new,
            "Generated tokens should not match.");

        list($login_old, $fetched_email_old, $token_id) = $login_model
            ->find_by_email_verification_token($token_old);

        $this->assertTrue(!(empty($login_old) || empty($fetched_email_old)),
            "Finding by old token should yield result.");
        $this->assertTrue($login->id == $login_old->id,
            "Old token should find the original login.");
        $this->assertEquals($login->email, $fetched_email_old,
            "Old token email should match current login.");

        list($login_new, $fetched_email_new, $token_id) = $login_model
            ->find_by_email_verification_token($token_new);

        $this->assertTrue(!(empty($login_new) || empty($fetched_email_new)),
            "Finding by new token should yield result.");
        $this->assertTrue($login->id == $login_new->id,
            "New token should find the original login.");
        $this->assertNotEquals($login->email, $fetched_email_new,
            "Old token email should match current new email.");

        // Try changing to new email.
        list($changed_login, $changed_email, $token_id) =
            $login_model->change_email_with_verification_token($token_new);
        $this->assertEquals($new_email, $changed_email,
            "Changed email should match new.");
        $this->assertEquals($login->id, $changed_login->id,
            "Changed login should match original id.");

        list($no_login, $no_email, $token_id) = $login_model
            ->find_by_email_verification_token($token_new);
        $this->assertTrue(empty($no_login) && empty($no_email),
            "Neither login nor email should be found for new token after use.");

        // Try reverting to old email.
        list($changed_login, $changed_email, $token_id) =
            $login_model->change_email_with_verification_token($token_old);
        $this->assertEquals($old_email, $changed_email,
            "Changed email should match new.");
        $this->assertEquals($login->id, $changed_login->id,
            "Changed login should match original id.");
        
        list($no_login, $no_email, $token_id) = $login_model
            ->find_by_email_verification_token($token_old);
        $this->assertTrue(empty($no_login) && empty($no_email),
            "Neither login nor email should be found for old token after use.");

        // Ensure that the oldest token wins, eg. the first "undo" in a chain 
        // of issued tokens will recover the original email address.
        //
        // This should help in the case where an unwanted email address change
        // is triggered multiple times, and an unwanted "undo" is hanging 
        // around out there.
        //
        // The very first undo, sent to the original email address, should serve
        // to back the whole thing out.
        
        $login = ORM::factory('login', 'tester42');
        $token_undo1 = $login->generate_email_verification_token();
        $token_new1  = $login->generate_email_verification_token($new_email);
        $login_model->change_email_with_verification_token($token_new1);

        $login = ORM::factory('login', 'tester42');
        $token_undo2 = $login->generate_email_verification_token();
        $token_new2  = $login->generate_email_verification_token('a@b');

        $login = ORM::factory('login', 'tester42');
        $token_undo3 = $login->generate_email_verification_token();
        $token_new3  = $login->generate_email_verification_token('b@a');

        $login_model->change_email_with_verification_token($token_new2);
        $login_model->change_email_with_verification_token($token_new3);
        $login_model->change_email_with_verification_token($token_undo1);
        $login_model->change_email_with_verification_token($token_undo3);
        $login_model->change_email_with_verification_token($token_undo2);

        $login = ORM::factory('login', 'tester42');
        $this->assertEquals('tester1@example.com', $login->email,
            "Original email should be restored.");

    }

    /**
     * Exercise account lockout on failed login.
     */
    public function test_failed_login_lockout()
    {
        $max_failed_logins = 5;
        $account_lockout_period = 5;

        Kohana::config_set('auth_profiles.max_failed_logins', 
            $max_failed_logins);
        Kohana::config_set('auth_profiles.account_lockout_period', 
            $account_lockout_period);

        $login_model = ORM::factory('login');

        ORM::factory('login')->set(array(
            'login_name' => 'tester42',
            'email'      => 'tester1@example.com',
        ))->save();

        $login = ORM::factory('login', 'tester42');
        $login->change_password('tester_password');

        $this->assertTrue(!$login->is_locked_out(),
            "Account should not be locked out on initial creation.");

        $data = array(
            'crumb'      => csrf_crumbs::generate(),
            'login_name' => $login->login_name,
            'password'   => 'tester_password'
        );
        $this->assertTrue($login_model->validate_login($data), 
            "Valid login should be valid.");

        for ($i=0; $i<$max_failed_logins; $i++) {
            $data = array(
                'crumb'      => csrf_crumbs::generate(),
                'login_name' => $login->login_name,
                'password'   => uniqid()
            );
            $this->assertTrue(!$login_model->validate_login($data), 
                "Invalid login should be invalid.");
        }

        $login = ORM::factory('login', 'tester42');
        $this->assertTrue($login->is_locked_out(),
            "Account should be locked out after invalid logins.");

        $data = array(
            'crumb'      => csrf_crumbs::generate(),
            'login_name' => $login->login_name,
            'password'   => 'tester_password'
        );
        $this->assertTrue(!$login_model->validate_login($data), 
            "Valid login should be invalid during account lockout.");

        sleep($account_lockout_period + 1);

        $data = array(
            'crumb'      => csrf_crumbs::generate(),
            'login_name' => $login->login_name,
            'password'   => 'tester_password'
        );
        $this->assertTrue($login_model->validate_login($data), 
            "Valid login should be valid after account lockout.");
        
    }

}
