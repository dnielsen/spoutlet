<?php

namespace Platformd\SpoutletBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class YouTubeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($this->isValidYouTubeId($value) === false) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }

    private function isValidYouTubeId($videoId)
    {
        if (!$videoId) {
            return false;
        }

        $url = 'http://gdata.youtube.com/feeds/api/videos/' . $videoId . '?alt=jsonc&v=2';

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));

        $result = json_decode(curl_exec($curl), true);

        if (isset($result)) {
            if (array_key_exists('error', $result)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}
