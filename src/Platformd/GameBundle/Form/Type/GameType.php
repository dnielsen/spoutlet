<?php

namespace Platformd\GameBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\GameBundle\Entity\Game;
use Platformd\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\FormBuilderInterface;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Game Name',
            ))
            ->add('category', 'choice', array(
                'choices' => self::getCategoryChoices(),
                'choices_as_values' => true,
                'label' => 'Genre',
            ))
            ->add('subcategories', 'choice', array(
                'choices' => self::getSubcategoryChoices(),
                'choices_as_values' => true,
                'label' => 'Subcategory',
                'multiple' => true,
                'expanded' => true,
            ))
            ->add('logo', new MediaType(), array(
                'image_label' => 'Game Logo',
                'image_help'  => 'Recommended size: 440x166',
            ))
            ->add('logoThumbnail', new MediaType(), array(
                'image_label' => 'Game Logo Thumbnail',
                'image_help'  => 'Recommended size: 195x80',
            ))
            ->add('publisherLogos', new MediaType(), array(
                'image_label' => 'Publisher/Developer Logos',
                'image_help'  => 'Recommended size: 634px wide or less and any height',
            ))
        ;
    }

    public function getName()
    {
        return 'platformd_gamebundle_gametype';
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
        $choices = ['Choose a Category' => ''];

        foreach ($values as $value) {
            $choices[Game::GAME_CATEGORY_LABEL_PREFIX.$value] = $value;
        }

        return $choices;
    }

    /**
     * Returns the choices for subcategory
     *
     * Labels are platformd.admin.games.subcategory.KEY
     *
     * @static
     * @return array
     */
    private static function getSubcategoryChoices()
    {
        $values = Game::getValidSubcategories();
        $choices = array();
        foreach ($values as $value) {
            $choices[$value]  = Game::GAME_SUBCATEGORY_LABEL_PREFIX.$value;
        }

        return $choices;
    }
}
