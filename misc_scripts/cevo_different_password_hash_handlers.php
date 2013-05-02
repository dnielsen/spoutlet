<?php

/*
We query the database using the user's login information.
Because there are different password hash types in the table, we
have to retrieve the record matching login username (or email) only.
We cannot match both username and password on the query itself because
we cannot predict the hash method in advance and we don't want to run a
bunch of queries to test each one.

So, we retrieve the user ID, password, and origin by matching the username or email.
Then we process it in the code.

****NOTE****
ALL $_POST data is automatically escaped before use!!!
We use a wrapper: xPost::Get($postVar)
You will undoubtedly have your own method to ensure the data is safe

*/


                    // if it's 32 chars then it's an old CGL-style password from Dell College Gaming League
                    // The Source Radio users might also have had this, I can't remember
                    if (strlen($r['password']) == 32)
                    {
                        // Its an old MD5, yuck!
                        if($r['password'] == md5(xPost::Get('password')))
                        {
                            // its a match, lets update their record to the new password!
                            $sql = "UPDATE ".CTEPrefix."users SET password = '".User::HashPassword(xPost::Get('password'))."' WHERE id = '".$r['id']."'";
                            $q = new DBQuery($sql);
                            $q->Execute();
                        }
                        else
                        {
                            $failed['password'] = 'Invalid Username/Password Combination';
                        }
                    }
                    // now we account for the PLDX accounts that we merged into the database
                    else if (strlen($r['password']) == 34 && substr($r['password'],0,3) == '$H$')
                    {
                        // looks like a PLDX login! PLDX used phpBB hashes
                        // NOTE: SEE BELOW FOR PLDX_check_hash() method
                        if ($this->PLDX_check_hash(xPost::Get('password'), $r['password']))
                        {
                            // worked
                            // now let's get rid of this old PHPBB hash
                            $sql = "UPDATE ".CTEPrefix."users SET password = '".User::HashPassword(xPost::Get('password'))."' WHERE id = '".$r['id']."'";
                            $q = new DBQuery($sql);
                            $q->Execute();
                        }
                        else
                        {
                            $failed['password'] = 'Invalid Username/Password Combination';
                        }
                    }
                    // now we account for the China/Japan users that were originally collected on the stand-alone Arena system
                    // these uers have an empty password in our user table, but their origin is 'cnjp'
                    else if (empty($r['password']) && $r['origin'] == 'cnjp')
                    {
                        $fosUser = new FOSUser();
                        // some of the usernames were appended with "_new" if there was a username collision in the system.
                        // this was the 'hacky' solution to bridge that gap for the end-user
                        $sql = 'SELECT * FROM '.$fosUser->GetDbTable().' fos WHERE username="'.str_replace('_new', '', $username).'"';
                        $db = new DBQuery($sql);
                        $db->Execute();
                        $r = $db->FetchRows();

                        if (count($r) > 0)
                        {
                            $fosUser = new FOSUser($r[0]);
                            $password = xPost::Get('password');
                            $fosHash =  bin2hex(hash('sha512', $password.'{'.$fosUser->salt.'}', true));

                            if ($fosUser->password == $fosHash)
                            {
                                //we need to update our password
                                $sql = 'UPDATE cte_users SET password="'.User::HashPassword(xPost::Get('password')).'" WHERE username="'.$username.'" AND origin="cnjp"';
                                $db = new DBQuery($sql);
                                $db->Execute();

                                //ok to login now
                                $r = $q->fetch();
                            }
                            else
                                $failed['password'] = 'Invalid Username/Password Combination';
                        }
                        else
                            $failed['password'] = 'Invalid Username/Password Combination';
                    }
                    // finally we check our own "normal" method by comparing the stored hash against the hash of what was posted to us
                    else if($r['password'] != User::HashPassword(xPost::Get('password')))
                    {
                        $failed['password'] = 'Invalid Username/Password Combination';
                    }







/*

Here is the method we use for User::HashPassword($password)

Looks like we've been using a single salt. This is bad practice.
Depending on ETA for user migration, we may change this on our side before migration occurs.


*/

    const HashSalt = '}$:&(#^>@<$!"^(#@!(}+=!';

    public static function HashPassword($pass)
    {
        $hash = hash('sha512', $pass.self::HashSalt);
        return $hash;
    }






/*

Here are all the methods involved in checking a PLDX hash.
I believe we ripped these from phpBB codebase or something and used them as-is except
for updating the method names.

Note that these are part of our AccountController class, which is why we call them with $this->$methodName

*/

    /* PLDX login stuffs */
      public function PLDX_check_hash($password, $hash)
        {
        // all hashes are 34 chars
        if (strlen($hash) != 34)
          return false;

            $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return ($this->PLDX_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
      }

      public function PLDX_hash_crypt_private($password, $setting, &$itoa64)
    {
        $output = '*';

        // Check for correct hash
        if (substr($setting, 0, 3) != '$H$')
        {
            return $output;
        }

        $count_log2 = strpos($itoa64, $setting[3]);

        if ($count_log2 < 7 || $count_log2 > 30)
        {
            return $output;
        }

        $count = 1 << $count_log2;
        $salt = substr($setting, 4, 8);

        if (strlen($salt) != 8)
        {
            return $output;
        }

        /**
        * We're kind of forced to use MD5 here since it's the only
        * cryptographic primitive available in all versions of PHP
        * currently in use.  To implement our own low-level crypto
        * in PHP would result in much worse performance and
        * consequently in lower iteration counts and hashes that are
        * quicker to crack (by non-PHP code).
        */
        if (PHP_VERSION >= 5)
        {
            $hash = md5($salt . $password, true);
            do
            {
                $hash = md5($hash . $password, true);
            }
            while (--$count);
        }
        else
        {
            $hash = pack('H*', md5($salt . $password));
            do
            {
                $hash = pack('H*', md5($hash . $password));
            }
            while (--$count);
        }

        $output = substr($setting, 0, 12);
        $output .= $this->PLDX_hash_encode64($hash, 16, $itoa64);

        return $output;
    }

    public function PLDX_hash_encode64($input, $count, &$itoa64)
    {
        $output = '';
        $i = 0;

        do
        {
            $value = ord($input[$i++]);
            $output .= $itoa64[$value & 0x3f];

            if ($i < $count)
            {
                $value |= ord($input[$i]) << 8;
            }

            $output .= $itoa64[($value >> 6) & 0x3f];

            if ($i++ >= $count)
            {
                break;
            }

            if ($i < $count)
            {
                $value |= ord($input[$i]) << 16;
            }

            $output .= $itoa64[($value >> 12) & 0x3f];

            if ($i++ >= $count)
            {
                break;
            }

            $output .= $itoa64[($value >> 18) & 0x3f];
        }
        while ($i < $count);

        return $output;
    }




?>
