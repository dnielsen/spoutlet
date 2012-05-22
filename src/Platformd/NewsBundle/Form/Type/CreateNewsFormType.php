<?php

namespace Platformd\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;

class CreateNewsFormType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('game', null, array('empty_value' => 'N/A'))
            ->add('title', 'textarea')
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/news/',
            ))
            ->add('postedAt', 'date', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'date-picker',
                ),
                'label' => 'posted_at',
                'format' => 'yyyy-MM-dd',
            ))
            ->add('overrideUrl', 'url', array(
                'label' => 'override_url',
                'required' => false
            ))
            ->add('blurb', 'textarea')
            ->add('body')
            ->add('locale', new SiteChoiceType())
            ->add('published', 'choice', array(
                'choices' => array(0 => 'Unpublished', 1 => 'Published'),
                'label'   => 'published_status',
            ))
            ->add('image', new MediaType(), array(
                'image_label'   => 'platformd.admin.news.upload_image',
                'image_help'    => 'platformd.admin.news.image_dimensions',
            ))
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'news';
    }
}
