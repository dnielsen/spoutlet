<?php

namespace Platformd\CEVOBundle\Password;

use Symfony\Component\Security\Core\User\UserInterface;
use Platformd\UserBundle\Exception\ApiRequestException;

class CEVOPasswordHandler
{
    const HASH_SALT = '}$:&(#^>@<$!"^(#@!(}+=!';

    private $userManager;

    public function __construct($userManager)
    {
        $this->userManager = $userManager;
    }

    private function onSuccessfulLogin($user)
    {
        try {
            $this->userManager->updateUserAndApi($user);
        } catch (ApiRequestException $e) {
        }
    }

    public function authenticate(UserInterface $user, $presentedPassword)
    {
        $dbPassword = $user->getPassword();

        // old CGL-style password from Dell College Gaming League / Source Radio
        if (strlen($dbPassword) == 32) {
            $success = $user->getPassword() == md5($presentedPassword);
        }

        // PLDX
        else if (strlen($dbPassword) == 34 && substr($dbPassword,0,3) == '$H$') {
            $success = $this->PLDX_check_hash($presentedPassword, $dbPassword);
        }

        // original standalone Japan/China users
        else if (empty($dbPassword)) {

            $altUser = $this->userManager->findUserByUsername(str_replace('_new', '', $user->getUsername()));

            if ($altUser) {
                $pwHash =  bin2hex(hash('sha512', $presentedPassword.'{'.$altUser->getSalt().'}', true));
                $success = $altUser->getPassword() == $pwHash;
            }

            $success = false;
        }

        // CEVO
        else {
            $success = $dbPassword == $this->hashPassword($presentedPassword);
        }

        if ($success) {
            $this->onSuccessfulLogin($user);
        }

        return $success;
    }

    private function hashPassword($pass)
    {
        $hash = hash('sha512', $pass.self::HASH_SALT);
        return $hash;
    }

    private function PLDX_check_hash($password, $hash)
    {
        // all hashes are 34 chars
        if (strlen($hash) != 34)
          return false;

            $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return ($this->PLDX_hash_crypt_private($password, $hash, $itoa64) === $hash) ? true : false;
      }

    private function PLDX_hash_crypt_private($password, $setting, &$itoa64)
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

    private function PLDX_hash_encode64($input, $count, &$itoa64)
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
}
