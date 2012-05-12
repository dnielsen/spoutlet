<?php

namespace Platformd\SpoutletBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Game;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class GameType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Game Name',
            ))
            ->add('slug', new SlugType())
            ->add('category', 'choice', array(
                'choices' => self::getCategoryChoices()
            ))
            ->add('facebookFanpageUrl', 'url', array(
                'label' => 'platformd.admin.facebook_fanpage'
            ))
            ->add('logo', new MediaType(), array(
                'image_label' => 'Game Logo',
                'image_help'  => 'Recommended size: 440x166',
            ))
            ->add('publisherLogos', new MediaType(), array(
                'image_label' => 'Publisher/Developer Logos',
                'image_help'  => 'Recommended size: 634px wide or less and any height',
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gametype';
    }

    /**
     * Returns the choices for category
     *
     * Labels are platformd.admin.games.category.KEY
     *
     * @static
     * @return array
     */
    private static function getCategoryChoices()
    {
        $values = Game::getValidCategories();
        $choices = array('' => 'Choose a Category');
        foreach ($values as $value) {
            $choices[$value]  = Game::GAME_CATEGORY_LABEL_PREFIX.$value;
        }

        return $choices;
    }
}
