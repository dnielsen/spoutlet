<?php

namespace Platformd\UserBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Collection;

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

    protected static $countries = array(
        'ja' => 'JP',
        'zh' => 'CN'
    );

    /**
     * @param string $class The User class name
     */
    public function __construct($class, array $sources = array(), Session $session, TranslatorInterface $translator)
    {
        parent::__construct($class);

        $this->locale = $session->getLocale();
        $this->sources = $sources;
        $this->translator = $translator;
    }

    public function setPrefectures(array $list)
    {
        $this->prefectures = isset($list[$this->locale]) ? $list[$this->locale] : array();
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('firstname', null, array('required' => true))
            ->add('lastname', null, array('required' => true))
            ->add('email', 'repeated', array('type' => 'email', 'required' => true))
            ->add('birthdate', 'birthday', array(
                'empty_value' => '--', 'required' => true,
                'years' => range(1940, date('Y')),
            ))
            ->add('phoneNumber', null, array('required' => false))
            ->add('hasAlienwareSystem', 'choice', array(
                'expanded' => true,
                'required' => true,
                'choices' => array(1 => 'Yes', 0 => 'No'),
                'empty_value' => '',
            ))
            ->add('latestNewsSource', 'choice', array(
                'empty_value' => 'Select one',
                'choices' => $this->sources,
                'required' => false,
            ))
            ->add('subscribedGamingNews')
            ->add('subscribedAlienwareEvents')
            ->add('termsAccepted', 'checkbox', array('required' => false));

        // if we have preferectures we use a choice
        if (sizeof((array)$this->prefectures) > 0) {
            $prefs = array();
            foreach ($this->prefectures as $prefecture) {
                $prefs[$prefecture] = $prefecture;
            }

            $builder->add('state', 'choice', array(
                'empty_value' => '',
                'choices' => $prefs,
                'required' => true
            ));
        } else {
            $builder->add('state', 'text', array('required' => true));
        }

        $countryOptions = array('required' => true);
        if (isset(self::$countries[$this->locale])) {
            $countryOptions['preferred_choices'] = array(self::$countries[$this->locale]);
        } else {
            $countryOptions['empty_value'] = 'platformd.user.register.country_label';
        }
        $builder->add('country', 'country', $countryOptions);

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

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Platformd\UserBundle\Entity\User',
        );
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
