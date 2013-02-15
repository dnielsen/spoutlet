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
                'help'  => 'Do not use the word "Official" in the group name. All groups containing the word "Official" will be renamed.',
            ))
            ->add('category', 'choice', array(
                'choices'   => self::getCategoryChoices(),
                'label'     => 'Group Category',
            ))
            ->add('groupAvatar', new MediaType(), array(
                'image_label'   => 'Group Banner',
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
            ->add('description', 'purifiedTextarea', array(
                'label' => 'Group Description*',
                'attr'  => array('class' => 'ckeditor')
            ))
            ->add('isPublic', 'choice', array(
                'choices'   => array(
                    1 => 'Public Group',
                    0 => 'Private Group',
                ),
                'expanded'  => true,
                'label'     => 'Group Visibility',
                'help'      => 'Public: Group information is visible to all users and any user can join your group. Private: Group information is visible to approved group members only. Group organizer must approve users to be in group.',
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
                }

                $builder->add('featured', 'checkbox', array(
                    'label' => 'Featured',
                    'help'  => 'Check this checkbox to make this group featured on the groups homepage.',
                ));

                $builder->add('discussionsEnabled', 'checkbox', array(
                    'label' => 'Discussions',
                    'help'  => 'Check this checkbox to enabled discussions for this group.',
                ));
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
