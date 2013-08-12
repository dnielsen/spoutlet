<?php

namespace Platformd\GameBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\GameBundle\Entity\Game;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\OpenGraphOverrideType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\GameBundle\Entity\GamePage;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class GamePageType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('recommendedDesktopUrl', null, array('label' => 'Recommended Desktop URL'))
            ->add('recommendedLaptopUrl', null, array(
                'label' => 'Recommended Laptop URL',
                'help'  => 'Provide both urls for recommended rigs in order for the ad to appear on the game page.'
            ))
            ->add('externalUrl', null, array('label' => 'External URL', 'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this game\'s Game Page.'))
            ->add('game', 'entity', array('class' => 'GameBundle:Game', 'empty_value' => 'N/A',
                'query_builder' => function(\Platformd\GameBundle\Entity\GameRepository $er) {
                    return $er->createQueryBuilder('g')
                              ->orderBy('g.name', 'ASC');
                    }))
            ->add('slug', new SlugType(), array('url_prefix' => '/games/genre/{game name}'))
            ->add('buttonImage1', new MediaType(), array(
                'image_label'   => 'Button Image 1',
                'image_help'    => 'Recommended size: 204x45',
            ))
            ->add('buttonUrl1', null, array(
                'label' => 'URL',
            ))
            ->add('buttonImage2', new MediaType(), array(
                'image_label'   => 'Button Image 2',
                'image_help'    => 'Recommended size: 204x45',
            ))
            ->add('buttonUrl2', null, array(
                'label' => 'URL',
            ))
            ->add('backgroundImage', new MediaType(), array(
                'image_label'   => 'Background Image',
                'image_help'    => 'Recommended width: 2000px with the center being 970 pixels wide and pure black.',
            ))
            ->add('aboutGame', 'purifiedTextarea', array(
                'label' => 'About the Game',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature1', 'purifiedTextarea', array(
                'label' => 'Key Feature 1',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature2', 'purifiedTextarea', array(
                'label' => 'Key Feature 2',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature3', 'purifiedTextarea', array(
                'label' => 'Key Feature 3',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('youtubeIdTrailer1', null, array(
                'label' => 'Feature Game Trailer Video',
                'help'  => 'Paste Youtube ID (e.g. UN6ZT9lDIOk if the URL is something like http://www.youtube.com/watch?v=UN6ZT9lDIOk&feature=fvst',
            ))
            ->add('youtubeIdTrailer2', null, array(
                'label' => 'Game Video 2',
                'help'  => 'Paste Youtube ID',
            ))
            ->add('youtubeIdTrailer3', null, array(
                'label' => 'Game Video 3',
                'help'  => 'Paste Youtube ID',
            ))
            ->add('youtubeIdTrailer4', null, array(
                'label' => 'Game Video 4',
                'help'  => 'Paste Youtube ID',
            ))
            ->add('youtubeIdTrailer1Headline', 'text', array(
                'label' => 'Game Feed Headline',
                'help'  => 'Must include game title in the context for SEO purposes.'
            ))
            ->add('youtubeIdTrailer2Headline', 'text', array(
                'label' => 'Game Feed Headline',
                'help'  => 'Must include game title in the context for SEO purposes.'
            ))
            ->add('youtubeIdTrailer3Headline', 'text', array(
                'label' => 'Game Feed Headline',
                'help'  => 'Must include game title in the context for SEO purposes.'
            ))
            ->add('youtubeIdTrailer4Headline', 'text', array(
                'label' => 'Game Feed Headline',
                'help'  => 'Must include game title in the context for SEO purposes.'
            ))
            ->add('legalVerbiage', 'purifiedTextarea', array(
                'label' => 'Legal Verbiage',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('sites', 'entity', array(
                'class'    => 'SpoutletBundle:Site',
                'multiple' => true,
                'expanded' => true,
                'property' => 'name',
            ))
            ->add('status', 'choice', array(
                'choices' => $this->getStatusChoices(),
            ))
            ->add('createdAt', 'datetime', array(
                'widget' => 'single_text',
                'attr'   => array(
                    'class' => 'datetime-picker',
                ),
                'help'   => 'Controls the order in which games are shown (newest first). You can leave this blank and it will be set automatically.',
                'label'  => 'created_date',
            ))
            ->add('mediaGalleryMedias', 'collection', array(
                'allow_add' => true,
                'allow_delete' => true,
                'type'      => new MediaType(),
                'options'   => array(
                    'image_label' => 'Media Image',
                    'image_help'  => 'Recommended size: 5333x3000, jpg format. For SEO purposes, file name should be in this format [game name]_wallpaper_0001.',
                )
            ))
            ->add('openGraphOverride', new OpenGraphOverrideType(), array('label' => 'Facebook Info'))
        ;
    }

    public function getStatusChoices()
    {
        foreach (GamePage::getValidStatues() as $status) {
            $choices[$status] = 'status.'.$status;
        }

        return $choices;
    }

    public function getName()
    {
        return 'platformd_gamebundle_gamepagetype';
    }
}
