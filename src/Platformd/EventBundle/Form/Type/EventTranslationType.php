<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\EventTranslation;
use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'platformd.event.form.name',
                'required' => false
            ])
            ->add('bannerImage', MediaType::class, [
                'image_label'   => 'platformd.event.form.banner_image',
                'image_help'    => 'platformd.event.form.help.banner_image',
                'required' => false
            ])
            ->add('content', PurifiedTextareaType::class, [
                'label' => 'platformd.event.form.description',
                'required' => false,
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => EventTranslation::class,
        ]);
    }

    public function getName()
    {
        return 'platformd_event_translation';
    }
}
