<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Collection;
use Platformd\SpoutletBundle\Entity\CountryRepository;
use Platformd\SpoutletBundle\Util\IpLookupUtil;

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
    private $translator;
    private $ipLookupUtil;
    private $request;
    private $includeRecaptcha;

    protected static $countries = array(
        'ja' => 'JP',
        'zh' => 'CN'
    );

    /**
     * @param string $class The User class name
     */
    public function __construct($class, array $sources = array(), Session $session, TranslatorInterface $translator, IpLookupUtil $ipLookupUtil, Request $request, $includeRecaptcha)
    {
        parent::__construct($class);

        $this->locale           = $session->getLocale();
        $this->sources          = $sources;
        $this->translator       = $translator;
        $this->ipLookupUtil     = $ipLookupUtil;
        $this->request          = $request;
        $this->includeRecaptcha = (bool)$includeRecaptcha;
    }

    public function setPrefectures(array $list)
    {
        $this->prefectures = isset($list[$this->locale]) ? $list[$this->locale] : array();
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        
        parent::buildForm($builder, $options);

        $builder
            ->add('username', null, array('required' => true, 'error_bubbling' => true))
            ->add('firstname', null, array('required' => true, 'error_bubbling' => true))
            ->add('lastname', null, array('required' => true, 'error_bubbling' => true))
            ->add('email', 'email', array('required' => true, 'error_bubbling' => true))
            ->add('plainPassword', 'password', array('required' => true, 'error_bubbling' => true, 'invalid_message' => 'passwords_do_not_match'))
            ->add('birthdate', 'birthday', array(
                'empty_value' => '--',
                'required' => true,
                'years' => range(date('Y'), 1920),
                'error_bubbling' => true,
            ))
            ->add('hasAlienwareSystem', 'choice', array(
                'expanded' => true,
                'choices' => array(1 => 'Yes', 0 => 'No'),
                'required' => true,
                'error_bubbling' => true,
            ))
            ->add('latestNewsSource', 'choice', array(
                'empty_value' => 'Select one',
                'choices' => $this->sources,
                'required' => false,
                'error_bubbling' => true,
            ))
            ->add('subscribedGamingNews')
            ->add('termsAccepted', 'checkbox', array('required' => false, 'error_bubbling' => true)); //required = false as this is not persisted to the DB

        // if we have preferectures we use a choice
        if (sizeof((array)$this->prefectures) > 0) {
            $prefs = array();
            foreach ($this->prefectures as $prefecture) {
                $prefs[$prefecture] = $prefecture;
            }

            $builder->add('state', 'choice', array(
                'empty_value' => '',
                'choices' => $prefs,
                'required' => true,
                'error_bubbling' => true,
            ));
        } else {
            $builder->add('state', 'choice', array(
                'choices'        => array(),
                'required'       => true,
                'error_bubbling' => true
            ));
        }

        $countryOptions = array(
            'required' => true,
            'error_bubbling' => true,
        );

        $builder->add('country', 'country', $countryOptions);
        $builder->add('name');

        if ($this->includeRecaptcha) {
            $builder->add('recaptcha', 'ewz_recaptcha', array(
                'attr' => array('options' => array(
                    'theme' => 'white',
                    'custom_translations' => array(
                        'instructions_visual'   => $this->translator->trans('recaptcha.instructions_visual'),
                        'visual_challenge'      => $this->translator->trans('recaptcha.visual_challenge'),
                        'audio_challenge'       => $this->translator->trans('recaptcha.audio_challenge'),
                        'refresh_btn'           => $this->translator->trans('recaptcha.refresh_btn'),
                        'instructions_context'  => $this->translator->trans('recaptcha.instructions_context'),
                        'instructions_audio'    => $this->translator->trans('recaptcha.instructions_audio'),
                        'help_btn'              => $this->translator->trans('recaptcha.help_btn'),
                        'play_again'            => $this->translator->trans('recaptcha.play_again'),
                        'cant_hear_this'        => $this->translator->trans('recaptcha.cant_hear_this'),
                        'incorrect_try_again'   => $this->translator->trans('recaptcha.incorrect_try_again'),
                        'image_alt_text'        => $this->translator->trans('recaptcha.image_alt_text'),
                    ),
                )),
                'property_path' => false,
            ));
        }

        $countryCode = $this->ipLookupUtil->getCountryCode($this->request->getClientIp());

        if($countryCode != 'US') {
            $builder->add('subscribedAlienwareEvents');
        }
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\UserBundle\Entity\User',
	    'csrf_protection' => false,
        );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'validation_groups' => array('Registration', 'IncompleteUser', 'Default'),
        ));
    }

    public function getName()
    {

        return 'platformd_user_registration';
    }

    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        parent::buildViewBottomUp($view, $form);

        // makes it so that the required label doesn't cascade down onto the two option labels
        $view['hasAlienwareSystem'][0]->set('required', false);
        $view['hasAlienwareSystem'][1]->set('required', false);
    }
}
