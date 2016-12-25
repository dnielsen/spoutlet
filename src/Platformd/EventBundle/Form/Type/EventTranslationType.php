<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\EventBundle\Entity\EventTranslation;
use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class EventTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'platformd.event.form.name',
                'required' => false
            ))
            ->add('bannerImage', new MediaType(), array(
                'image_label'   => 'platformd.event.form.banner_image',
                'image_help'    => 'platformd.event.form.help.banner_image',
                'required' => false
            ))
            ->add('content', 'purifiedTextarea', array(
                'label' => 'platformd.event.form.description',
                'required' => false,
                'attr' => array(
                    'class' => 'ckeditor'
                )
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
