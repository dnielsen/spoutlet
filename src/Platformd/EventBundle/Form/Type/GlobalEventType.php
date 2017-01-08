<?php

namespace Platformd\EventBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\GmtOffsetTimezoneType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class GlobalEventType extends EventType
{
    const PUBLISH = [
        'platformd.event.form.choice.published' => 1,
        'platformd.event.form.choice.unpublished' => 0,
    ];

    private $tagManager;

    public function __construct($tagManager)
    {
        $this->tagManager = $tagManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('hostedBy', TextType::class, array(
                'label' => 'platformd.event.form.hosted_by',
                'required' => false
            ))
            ->add('externalUrl', TextType::class, array(
                'label' => 'platformd.event.form.external_url',
                'help' => 'platformd.event.form.help.external_url',
                'required' => false
            ))
            ->add('published', ChoiceType::class, array(
                'choices' => self::PUBLISH,
                'choices_as_values' => true,
                'label' => 'platformd.event.form.published',
                'expanded' => true
            ))
            ->add('translations', CollectionType::class, array(
                'entry_type' => new GlobalEventTranslationType,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
                'required' => false
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name'
            ))
            ->add('slug', SlugType::class, array(
                'label' => 'platformd.event.form.url'
            ))
            ->add('timezone', GmtOffsetTimezoneType::class, [
                'label' => 'platformd.event.form.timezone',
            ])
            ->add('tags', TextType::class, array(
                'label' => 'platformd.event.form.tags',
                'help' => 'platformd.event.form.tags_help',
                'data' => $builder->getData() ? $this->tagManager->getConcatenatedTagNames($builder->getData()) : null,
                'required' => false,
            ));
    }
}
