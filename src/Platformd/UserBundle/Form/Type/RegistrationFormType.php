<?php

namespace Platformd\UserBundle\Form\Type;

use Platformd\UserBundle\Entity\User;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Platformd\SpoutletBundle\Util\IpLookupUtil;

class RegistrationFormType extends BaseType
{
    /**
     * @var array source the user may have heard about alienware arena from
     */
    private $sources;

    /**
     * @var array A potential list of (japaneses ?) prefectures
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

    protected static $countries = [
        'ja' => 'JP',
        'zh' => 'CN'
    ];

    /**
     * @param string $class The User class name
     */
    public function __construct($class, array $sources = [], TranslatorInterface $translator, IpLookupUtil $ipLookupUtil, Request $request, $includeRecaptcha)
    {
        parent::__construct($class);

        $this->locale = $request->getLocale();
        $this->sources = $sources;
        $this->translator = $translator;
        $this->ipLookupUtil = $ipLookupUtil;
        $this->request = $request;
        $this->includeRecaptcha = (bool)$includeRecaptcha;
    }

    public function setPrefectures(array $list)
    {
        $this->prefectures = isset($list[$this->locale]) ? $list[$this->locale] : [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
            ])
//            ->add('firstname', null, array('required' => true, 'error_bubbling' => true))
//            ->add('lastname', null, array('required' => true, 'error_bubbling' => true))
            ->add('email', EmailType::class, array('required' => true, 'error_bubbling' => true))
            ->add('plainPassword', RepeatedType::class, array(
                'type' => PasswordType::class,
                'first_options' => array('label' => 'Password:', 'required' => true),
                'second_options' => array('label' => 'Confirm Password:', 'required' => true),
                'invalid_message' => 'passwords_do_not_match',
            ));
//            ->add('plainPassword', 'repeated', array(
//                'required' => true,
//                'error_bubbling' => true,
//                'invalid_message' => 'passwords_do_not_match'
//            ));
//            ->add('birthdate', 'birthday', array(
//                'empty_value' => '--',
//                'required' => true,
//                'years' => range(date('Y'), 1920),
//                'error_bubbling' => true,
//            ))
//            ->add('hasAlienwareSystem', 'choice', array(
//                'expanded' => true,
//                'choices' => [
//                    1 => 'Yes',
//                    0 => 'No',
//                ],
//                'required' => true,
//                'error_bubbling' => true,
//            ))
//            ->add('latestNewsSource', 'choice', array(
//                'empty_value' => 'Select one',
//                'choices' => $this->sources,
//                'required' => false,
//                'error_bubbling' => true,
//            ))
//            ->add('subscribedGamingNews')
//            ->add('termsAccepted', 'checkbox', array('required' => false, 'error_bubbling' => true));

        // if we have preferectures we use a choice
//        if (count((array)$this->prefectures) > 0) {
//            $prefs = array();
//            foreach ($this->prefectures as $prefecture) {
//                $prefs[$prefecture] = $prefecture;
//            }
//
//            $builder->add('state', 'choice', array(
//                'empty_value' => '',
//                'choices' => $prefs,
//                'required' => true,
//                'error_bubbling' => true,
//            ));
//        } else {
//            $builder->add('state', 'text', array('required' => true, 'error_bubbling' => true));
//        }
//
//        $builder->add('country', 'country', array(
//            'required' => true,
//            'error_bubbling' => true,
//        ));

//        if ($this->includeRecaptcha) {
//            $builder->add('recaptcha', 'ewz_recaptcha', array(
//                'attr' => array('options' => array(
//                    'theme' => 'white',
//                    'custom_translations' => array(
//                        'instructions_visual' => $this->translator->trans('recaptcha.instructions_visual'),
//                        'visual_challenge' => $this->translator->trans('recaptcha.visual_challenge'),
//                        'audio_challenge' => $this->translator->trans('recaptcha.audio_challenge'),
//                        'refresh_btn' => $this->translator->trans('recaptcha.refresh_btn'),
//                        'instructions_context' => $this->translator->trans('recaptcha.instructions_context'),
//                        'instructions_audio' => $this->translator->trans('recaptcha.instructions_audio'),
//                        'help_btn' => $this->translator->trans('recaptcha.help_btn'),
//                        'play_again' => $this->translator->trans('recaptcha.play_again'),
//                        'cant_hear_this' => $this->translator->trans('recaptcha.cant_hear_this'),
//                        'incorrect_try_again' => $this->translator->trans('recaptcha.incorrect_try_again'),
//                        'image_alt_text' => $this->translator->trans('recaptcha.image_alt_text'),
//                    ),
//                )),
//                'property_path' => 'recaptcha',
//            ));
//        }

//        $countryCode = $this->ipLookupUtil->getCountryCode($this->request->getClientIp());
//
//        if ($countryCode !== 'US') {
//            $builder->add('subscribedAlienwareEvents');
//        }

//        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
//            /** @var User $user */
//            $user = $event->getData();
//
//            if (!$user->getUsername()) {
//                $user->setUsername($user->getEmail());
//            }
//
//            $event->setData($user);
//        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['Registration', 'IncompleteUser', 'Default'],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'platformd_user_registration';
    }
}
