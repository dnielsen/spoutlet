<?php

namespace Platformd\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;

class CreateNewsFormType extends AbstractType
{

    private $news;
    private $tagManager;

    public function __construct($news, $tagManager)
    {
        $this->news         = $news;
        $this->tagManager   = $tagManager;
    }

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
                    'class' => 'datetime-picker',
                ),
                'label' => 'posted_at',
                'format' => 'yyyy-MM-dd',
            ))
            ->add('overrideUrl', 'url', array(
                'label' => 'override_url',
                'required' => false
            ))
            ->add('blurb', 'textarea')
            ->add('body', 'purifiedTextarea', array(
                'attr'  => array('class' => 'ckeditor'),
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('published', 'choice', array(
                'choices' => array(0 => 'Unpublished', 1 => 'Published'),
                'label'   => 'published_status',
            ))
            ->add('image', new MediaType(), array(
                'image_label'   => 'platformd.admin.news.upload_image',
                'image_help'    => 'platformd.admin.news.image_dimensions',
                'with_remove_checkbox' => true
            ))
        ;

        $builder->add('tags', 'text', array(
            'label' => 'platformd.admin.news.tags',
            'help' => "platformd.admin.news.tags_help",
            'property_path' => false,
            'data' => $this->news ? $this->tagManager->getConcatenatedTagNames($this->news) : null,
            'required' => false,
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'news';
    }
}
