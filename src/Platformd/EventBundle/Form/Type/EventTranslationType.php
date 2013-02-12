<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilder
;

use Platformd\EventBundle\Entity\GroupEventTranslation;
use Platformd\MediaBundle\Form\Type\MediaType;

class EventTranslationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
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

    public function getName()
    {
        return 'platformd_event_translation';
    }

    public function getDefaultOptions(array $options)
    {
        $options['data_class'] = 'Platformd\EventBundle\Entity\EventTranslation';

        return $options;
    }
}
