<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class GamePageType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('game')
            ->add('slug', new SlugType())
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
                'image_help'    => 'Recommended Size: Huge, but with a 980 pixel black gap',
            ))
            ->add('aboutGame', null, array('label' => 'About the Game'))
            ->add('keyFeature1', null, array('label' => 'Key Feature 1'))
            ->add('keyFeature2', null, array('label' => 'Key Feature 2'))
            ->add('keyFeature3', null, array('label' => 'Key Feature 3'))
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
            ->add('legalVerbiage')
            ->add('locales', new SiteChoiceType(), array(
                'multiple' => true,
                'expanded' => true,
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gamepagetype';
    }
}
