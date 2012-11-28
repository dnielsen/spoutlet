<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class YouTubeValidator extends ConstraintValidator
{

    private function isValidYouTubeId($videoId)
    {
        if (!$videoId) {
            return false;
        }

        $url = 'http://gdata.youtube.com/feeds/api/videos/' . $videoId . '?alt=jsonc&v=2';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        if(isset($result))
        {
            if(array_key_exists('error', $result))
            {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public function isValid($videoId, Constraint $constraint)
    {
        if($this->isValidYouTubeId($videoId) === false) {
            $this->setMessage($constraint->message, array('{{ value }}' => $videoId));

            return false;
        }

        return true;
    }
}
