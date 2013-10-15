<?php

namespace Platformd\IdeaBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use FOS\UserBundle\Form\Type\RegistrationFormType as BaseType;
use Platformd\UserBundle\Entity\User;

class RegistrationFormType extends BaseType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('name');
        $builder->add('organization');
        $builder->add('title');
        $builder->add('industry');

        /*
        $user = new User();
        $builder->add('school');
        $builder->add('major');
        $builder->add('svicRole', 'choice',
            array('choices'=>array(
                'Entrant'=>'Entrant',
                'Judge'=>'Judge',
                'Observer'=>'Observer',
                'Volunteer'=>'Volunteer',
                'Organizer'=>'Organizer',
                'Other'=>'Other')) );
        $builder->add('affiliation', 'choice',
            array('choices'=>array(
                'Student'=>'Student',
                'Alumni'=>'Alumni',
                'Staff'=>'Staff',
                'Faculty'=>'Faculty',
                'Investor'=>'Investor',
                'Mentor'=>'Mentor',
                'Partner'=>'Partner',
                'Other'=>'Other')) );
        */

    }

    public function getName()
    {
        return 'idea_user_registration';
    }
}
