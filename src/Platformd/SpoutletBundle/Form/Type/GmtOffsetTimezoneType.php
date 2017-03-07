<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class GmtOffsetTimezoneType
 */
class GmtOffsetTimezoneType extends AbstractType
{
    /**
     * Stores the available timezone choices
     * @var array
     */
    private static $timezones;

    /**
     * Stores the available timezone choices for admin pages
     * @var array
     */
    private static $basicTimezones;

    private static $adminTimezones = array(
        'America/Chicago' => 'Central',
        'America/New_York' => 'Eastern',
        'America/Phoenix' => 'Mountain',
        'America/Los_Angeles' => 'Pacific',
        'Europe/London' => null,
        'Europe/Paris' => 'Central Europe',
        'Europe/Istanbul' => 'Eastern Europe',
        'Asia/Bangkok' => 'China',
        'Asia/Kuala_Lumpur' => 'Malaysia',
        'Asia/Tokyo' => 'Japan',
        'Australia/Perth' => 'Western Australia',
        'Asia/Singapore' => 'Singapore',
        'Australia/Adelaide' => 'Central Australia',
        'Australia/Melbourne' => 'Eastern Australia',
        'Asia/Kolkata' => 'India',
        'UTC' => null,
    );

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $choices = isset($options['full']) ? self::getTimezones($options['full']) : self::getTimezones();

        $resolver->setDefaults([
            'choice_list' => new ArrayChoiceList($choices),
            'full' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TimezoneType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'gmtTimezone';
    }

    /**
     * Returns the timezone choices.
     *
     * The choices are generated from the ICU function
     * \DateTimeZone::listIdentifiers(). They are cached during a single request,
     * so multiple timezone fields on the same page don't lead to unnecessary
     * overhead.
     *
     * @return array The timezone choices
     */
    public static function getTimezones($fullList = false)
    {
        // used to output the abbreviated timezone name for each full timezone name
        $tempDateTime = new \DateTime();
        $timezoneArr = array();

        if ($fullList == false) {

            if (null === static::$basicTimezones) {
                static::$basicTimezones = array();

                foreach (self::$adminTimezones as $timezone => $name) {
                    $tempDateTime->setTimeZone(new \DateTimeZone($timezone));
                    $offset = $tempDateTime->format('Z');

                    $timezoneArr[$timezone] = $offset;
                }

                asort($timezoneArr);

                foreach ($timezoneArr as $timezone => $offset) {
                    $tempDateTime->setTimeZone(new \DateTimeZone($timezone));

                    $abbreviation = $tempDateTime->format('T');
                    $gmtDiff = $tempDateTime->format('P');

                    $string = '(GMT ' . $gmtDiff . ') ' . (self::$adminTimezones[$timezone] ? '- ' . self::$adminTimezones[$timezone] . ' - ' : '') . $abbreviation;

                    static::$basicTimezones[$timezone] = str_replace('_', ' ', $string);
                }
            }

            return array_flip(static::$basicTimezones);
        } else {
            if (null === static::$timezones) {
                static::$timezones = array();

                foreach (\DateTimeZone::listIdentifiers() as $timezone) {

                    $tempDateTime->setTimeZone(new \DateTimeZone($timezone));
                    $offset = $tempDateTime->format('Z');

                    $timezoneArr[$timezone] = $offset;
                }

                asort($timezoneArr);

                foreach ($timezoneArr as $timezone => $offset) {
                    $tempDateTime->setTimeZone(new \DateTimeZone($timezone));

                    $abbreviation = $tempDateTime->format('T');
                    $gmtDiff = $tempDateTime->format('P');
//                    $identifier     = $tempDateTime->format('e');

                    $parts = explode('/', $timezone);

                    if (count($parts) > 2) {
                        $name = $parts[1] . ' - ' . $parts[2];
                    } elseif (count($parts) > 1) {
                        $name = $parts[1];
                    } else {
                        $name = $parts[0];
                    }

                    static::$timezones[$timezone] = str_replace('_', ' ', '(GMT ' . $gmtDiff . ') ' . $name . ' (' . $abbreviation . ')');
                }
            }

            return static::$timezones;
        }
    }
}
