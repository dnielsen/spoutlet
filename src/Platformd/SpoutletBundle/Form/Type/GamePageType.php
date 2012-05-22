<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Entity\GamePage;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class GamePageType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('recommendedDesktopUrl', null, array('label' => 'Recommended Desktop URL'))
            ->add('recommendedLaptopUrl', null, array('label' => 'Recommended Laptop URL'))
            ->add('externalUrl', null, array('label' => 'External URL', 'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this game\'s Game Page.'))
            ->add('game', null, array('empty_value' => 'N/A'))
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
                'image_help'    => 'Recommended Size: Huge, but with a 970 pixel black gap',
            ))
            ->add('aboutGame', null, array(
                'label' => 'About the Game',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature1', null, array(
                'label' => 'Key Feature 1',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature2', null, array(
                'label' => 'Key Feature 2',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('keyFeature3', null, array(
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
            ))
            ->add('youtubeIdTrailer2Headline', 'text', array(
                'label' => 'Game Feed Headline',
            ))
            ->add('youtubeIdTrailer3Headline', 'text', array(
                'label' => 'Game Feed Headline',
            ))
            ->add('youtubeIdTrailer4Headline', 'text', array(
                'label' => 'Game Feed Headline',
            ))
            ->add('legalVerbiage', null, array(
                'label' => 'Legal Verbiage',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('locales', new SiteChoiceType(), array(
                'multiple' => true,
                'expanded' => true,
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
                    'image_help'  => 'This can be any size, and will probably be quite large.',
                )
            ))
        ;
    }

    public function getStatusChoices()
    {
        $choices = array(
            '' => 'status.choose_status',
        );

        foreach (GamePage::getValidStatues() as $status) {
            $choices[$status] = 'status.'.$status;
        }

        return $choices;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gamepagetype';
    }
}
