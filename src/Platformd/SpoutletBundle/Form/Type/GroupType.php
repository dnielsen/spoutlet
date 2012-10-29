<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Group;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;
use Platformd\SpoutletBundle\Form\Type\LocationType;
use Platformd\UserBundle\Entity\User;

class GroupType extends AbstractType
{

    private $user;
    private $group;

    public function __construct($user, $group) {
        $this->user = $user;
        $this->group = $group;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Group Name*',
            ))
            ->add('category', 'choice', array(
                'choices' => self::getCategoryChoices(),
                'label' => 'Group Category',
            ))
            ->add('groupAvatar', new MediaType(), array(
                'image_label'   => 'Group Avatar',
                'image_help'    => 'Recommended size: 950x120',
                'with_remove_checkbox' => $this->group->getId() == null ? false : true
            ))
            ->add('backgroundImage', new MediaType(), array(
                'image_label'   => 'Background Image',
                'image_help'    => 'Recommended width: 2000px with the center being 970 pixels wide and pure black.',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('thumbNail', new MediaType(), array(
                'image_label'   => 'Thumbnail Image',
                'image_help'    => 'Recommended size: 135x80',
                'with_remove_checkbox' => $this->group->getId() == 0 ? false : true
            ))
            ->add('description', null, array(
                'label' => 'Information about Group*',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('isPublic', 'checkbox', array(
                'required' => true,
                'label' => 'Is this a public group?',
                'help' => 'Yes (If left unchecked, you will need to approve users in order to give them access to your group page.)',
            ))
            ->add('location', new LocationType(), array(
                'label' => 'Location',
            ));

            if ($this->user instanceof User && $this->user->hasRole('ROLE_SUPER_ADMIN')) {

                $builder->add('sites', 'entity', array(
                    'class'    => 'SpoutletBundle:Site',
                    'multiple' => true,
                    'expanded' => true,
                    'property' => 'name'
                ))
                ->add('allLocales', 'checkbox', array('label' => 'Enable for all Locales', 'help' => 'If set to true this overrides the "locales" setting and sets this group to be visible to all sites'));


                if ($this->group->getId() > 0) {
                    $builder->add('deleted', 'checkbox', array(
                        'label' => 'Disable Group', 'help' => 'Use this to administratively disable this group.',
                    ));

                    $builder->add('featured', 'checkbox', array(
                        'label' => 'Featured',
                        'help'  => 'Check this checkbox to make this group featured on the groups homepage.',
                    ));
                }
            }
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_grouptype';
    }

    /**
     * Returns the choices for category
     *
     * Labels are platformd.admin.groups.category.KEY
     *
     * @static
     * @return array
     */
    private static function getCategoryChoices()
    {
        $values = Group::getValidCategories();

        foreach ($values as $value) {
            $choices[$value]  = Group::GROUP_CATEGORY_LABEL_PREFIX.$value;
        }

        return $choices;
    }
}
