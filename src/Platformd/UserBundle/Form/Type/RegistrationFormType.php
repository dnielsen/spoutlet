<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\HttpFoundation\Session;

class RegistrationFormType extends BaseType
{
    /**
     * @var array source the user may have heard about alienware arena from
     */
    private $sources;

    /**
     * @var A potential list of (japaneses ?) prefectures
     */
    private $prefectures = array();
    
    /**
     * @var String user's locale
     */
    private $locale;

    protected static $countries = array(
        'ja' => 'JP',
        'zh' => 'CN'
    );

    /**
     * @param string $class The User class name
     */
    public function __construct($class, array $sources = array(), Session $session)
    {
        parent::__construct($class);

        $this->locale = $session->getLocale();
        $this->sources = $sources;
    }
    
    public function setPrefectures(array $list)
    {
        $this->prefectures = isset($list[$this->locale]) ? $list[$this->locale] : array();
    }
    
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('firstname')
            ->add('lastname')
            ->add('email', 'repeated', array('type' => 'email'))
            ->add('birthdate', 'birthday', array('empty_value' => '--'))
            ->add('phoneNumber')
            ->add('hasAlienwareSystem', 'choice', array(
                'expanded' => true,
                'required' => true,
                'choices' => array(1 => 'Yes', 0 => 'No'),
                'empty_value' => '',
            ))
            ->add('latestNewsSource', 'choice', array(
                'empty_value' => 'Select one',
                'choices' => $this->sources
            ))
            ->add('subscribedGamingNews')
            ->add('termsAccepted', 'checkbox', array('required' => false));
        
        // if we have preferectures we use a choice
        if (sizeof((array)$this->prefectures) > 0) {
            $prefs = array();
            foreach ($this->prefectures as $prefecture) {
                $prefs[$prefecture] = $prefecture;
            }

            $builder->add('state', 'choice', array(
                'empty_value' => '',
                'choices' => $prefs
            ));
        } else {
            $builder->add('state', 'text');
        }

        $countryOptions = array();
        if (isset(self::$countries[$this->locale])) {
            $countryOptions['preferred_choices'] = array(self::$countries[$this->locale]);
        } else {
            $countryOptions['empty_value'] = 'platformd.user.register.country_label'; 
        }
        $builder->add('country', 'country', $countryOptions);
    }

    public function getName()
    {
        
        return 'patformd_user_registration';
    }
}
