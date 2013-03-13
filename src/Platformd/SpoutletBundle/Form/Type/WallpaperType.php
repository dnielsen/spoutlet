<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Wallpaper;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\MediaBundle\Form\Type\MediaZipType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Event\DataEvent;

class WallpaperType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('thumbnail', new MediaType(), array(
                'image_label' => 'Wallpaper Thumbnail',
                'image_help'  => 'Recommended size: 310x230',
            ))
            ->add('resolutionPack', new MediaType(), array(
                'image_label' => 'Resolution Pack',
                'image_help'  => 'This needs to be a zip file that contains multiple different copies of the wallpaper at different resolutions',
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_wallpapertype';
    }
}
