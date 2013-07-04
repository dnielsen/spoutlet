<?php

namespace Platformd\EventBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;

use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\TagBundle\Model\TaggableInterface;

class GlobalEventType extends EventType
{
    private $event;
    private $tagManager;

    public function __construct($event, $tagManager)
    {
        $this->event        = $event;
        $this->tagManager   = $tagManager;
    }

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
            ->add('timezone', 'gmtTimezone', array(
                'label' => 'platformd.event.form.timezone',
            ))
        ;

        $builder->add('tags', 'text', array(
                'label' => 'platformd.event.form.tags',
                'help' => 'platformd.event.form.tags_help',
                'property_path' => false,
                'data' => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
                'required' => false,
            ))
        ;
    }
}
