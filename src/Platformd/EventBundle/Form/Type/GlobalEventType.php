<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;

class GlobalEventType extends EventType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('hostedBy', 'text', array(
                'label' => 'platformd.event.form.hosted_by',
                'required' => false
            ))
            ->add('externalUrl', 'text', array(
                'label' => 'platformd.event.form.external_url',
                'help' => 'platformd.event.form.help.external_url',
                'required' => false
            ))
            ->add('published', 'choice', array(
                'choices' => array(
                    1 => 'platformd.event.form.choice.published',
                    0 => 'platformd.event.form.choice.unpublished'
                ),
                'label' => 'platformd.event.form.published',
                'expanded' => true
            ))
            ->add('translations', 'collection', array(
                'type' => new GlobalEventTranslationType,
                'allow_add'      => false,
                'allow_delete'   => false,
                'by_reference' => false,
                'required' => false
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name'
            ))
            ->add('slug', new SlugType(), array(
                'label' => 'platformd.event.form.url'
            ))
        ;
    }
}
