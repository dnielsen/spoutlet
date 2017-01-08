<?php

namespace Platformd\NewsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\NewsBundle\Entity\News;
use Symfony\Component\Form\FormBuilderInterface;

class CreateNewsFormType extends AbstractType
{
    const PUBLISHED_CHOICE = [
        'Unpublished' => 0,
        'Published' => 1,
    ];

    private $news;
    private $tagManager;

    public function __construct($news, $tagManager)
    {
        $this->news = $news;
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('game', null, array(
                'empty_value' => 'N/A',
            ))
            ->add('title', 'text')
            ->add('slug', new SlugType(), array(
                'url_prefix' => '/news/',
            ))
            ->add('postedAt', 'date', array(
                'widget' => 'single_text',
                'attr' => array(
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
                'attr' => array(
                    'class' => 'ckeditor',
                ),
            ))
            ->add('sites', 'entity', array(
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('published', 'choice', array(
                'choices' => self::PUBLISHED_CHOICE,
                'choices_as_values' => true,
                'label' => 'published_status',
            ))
            ->add('image', new MediaType(), array(
                'image_label' => 'platformd.news.admin.upload_image',
                'image_help' => 'platformd.news.admin.image_dimensions',
                'with_remove_checkbox' => true,
            ))
            ->add('type', 'choice', array(
                'choices' => $this->getTypeChoices(),
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'news-type',
                ),
            ))
            ->add('thumbnail', new MediaType(), array(
                'image_label' => 'platformd.news.admin.article_thumbnail',
                'image_help' => 'platformd.news.admin.thumbnail_dimensions',
                'with_remove_checkbox' => true,
            ));

        $builder->add('tags', 'text', array(
            'label' => 'platformd.news.admin.tags',
//            'help' => "platformd.news.admin.tags_help",
            'mapped' => false,
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

    private function getTypeChoices()
    {
        $choices = [];

        foreach (News::getTypes() as $type) {
            $choices[ucfirst($type)] = $type;
        }

        return $choices;
    }
}
