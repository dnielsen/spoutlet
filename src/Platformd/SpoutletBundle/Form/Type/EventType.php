<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class EventType extends AbstractType
{
    protected $nonRequiredFields = array(
        'slug',
        'starts_at',
        'ends_at',
        'location',
        'city',
        'country',
        'hosted_by',
        'game',
        'externalUrl',
        'bannerImageFile',
        'description',
        'timezone',
    );

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextareaType::class);
        $builder->add('slug', new SlugType());
        $builder->add('externalUrl', null, array('label' => 'External URL', 'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this Event page.'));

        $this->createStartsAtField($builder);
        $this->createEndsAtField($builder);

        $builder->add('timezone', 'gmtTimezone');//TODO: fix timezone
        $builder->add('display_timezone', null, array(
            'label' => 'Display Timezone',
        ));

        $builder->add('city', TextType::class);
        $builder->add('country', TextType::class);
        $builder->add('content', TextareaType::class);
        $builder->add('hosted_by', TextType::class);
        $builder->add('gameStr', TextType::class, array('label' => 'Game Name (don\'t use anymore)'));
        $builder->add('game', null, array('empty_value' => 'N/A'));
        $builder->add('location', TextType::class);
        $builder->add('bannerImageFile', FileType::class);
        $builder->add('sites', EntityType::class, array(
            'class' => 'SpoutletBundle:Site',
            'multiple' => true,
            'expanded' => true,
            'choice_label' => 'name',
        ));


        $this->unrequireFields($builder);
    }

    /**
     * Utility function to properly mark fields as required/not-required
     *
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    protected function unrequireFields(FormBuilderInterface $builder)
    {
        foreach ($this->nonRequiredFields as $name) {
            if ($builder->has($name)) {
                $builder->get($name)->setRequired(false);
            }
        }
    }

    public function getBlockPrefix()
    {
        return 'event';
    }

    protected function createStartsAtField(FormBuilderInterface $builder)
    {
        return $builder->add('starts_at', DateTimeType::class, array(
            'widget' => 'single_text',
            'attr' => array(
                'class' => 'datetime-picker',
            )
        ));
    }

    protected function createEndsAtField(FormBuilderInterface $builder)
    {
        return $builder->add('ends_at', DateTimeType::class, array(
            'widget' => 'single_text',
            'attr' => array(
                'class' => 'datetime-picker',
            )
        ));
    }
}
