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
            ->add('slug')
            ->add('buttonImage1')
            ->add('buttonUrl1')
            ->add('buttonImage2')
            ->add('buttonUrl2')
            ->add('backgroundImage')
            ->add('aboutGame')
            ->add('keyFeature1')
            ->add('keyFeature2')
            ->add('keyFeature3')
            ->add('youtubeIdTrailer1')
            ->add('youtubeIdTrailer2')
            ->add('youtubeIdTrailer3')
            ->add('youtubeIdTrailer4')
            ->add('legalVerbiage')
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gamepagetype';
    }
}
