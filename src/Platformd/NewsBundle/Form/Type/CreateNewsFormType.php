<?php

namespace Platformd\NewsBundle\Form\Type;

use Platformd\SpoutletBundle\Form\Type\PurifiedTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\NewsBundle\Entity\News;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
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
                'label' => 'Game',
                'empty_value' => 'N/A',
            ))
            ->add('title', TextType::class, [
                'label' => 'Title',
            ])
            ->add('slug', SlugType::class, array(
                'label' => 'URL string - /news/',
            ))
            ->add('postedAt', DateType::class, array(
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker',
                ),
                'label' => 'posted_at',
                'format' => 'yyyy-MM-dd',
            ))
            ->add('overrideUrl', UrlType::class, array(
                'label' => 'override_url',
                'required' => false
            ))
            ->add('blurb', TextareaType::class, [
                'label' => 'Blurb',
            ])
            ->add('body', PurifiedTextareaType::class, array(
                'label' => 'Body',
                'attr' => array(
                    'class' => 'ckeditor',
                ),
            ))
            ->add('sites', EntityType::class, array(
                'label' => 'Sites',
                'class' => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'choice_label' => 'name',
            ))
            ->add('published', ChoiceType::class, array(
                'choices' => self::PUBLISHED_CHOICE,
                'choices_as_values' => true,
                'label' => 'published_status',
            ))
            ->add('image', MediaType::class, array(
                'image_label' => 'platformd.news.admin.upload_image',
                'image_help' => 'platformd.news.admin.image_dimensions',
                'with_remove_checkbox' => true,
            ))
            ->add('type', ChoiceType::class, array(
                'label' => 'Type',
                'choices' => $this->getTypeChoices(),
                'choices_as_values' => true,
                'attr' => array(
                    'class' => 'news-type',
                ),
            ))
            ->add('thumbnail', MediaType::class, array(
                'image_label' => 'platformd.news.admin.article_thumbnail',
                'image_help' => 'platformd.news.admin.thumbnail_dimensions',
                'with_remove_checkbox' => true,
            ));

        $builder->add('tags', TextType::class, array(
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
    public function getBlockPrefix()
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
