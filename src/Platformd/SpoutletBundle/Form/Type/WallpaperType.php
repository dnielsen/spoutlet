<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class WallpaperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
