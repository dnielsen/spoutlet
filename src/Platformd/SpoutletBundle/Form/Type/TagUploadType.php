<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class TagUploadType extends AbstractType
{
    /**
     * Configures a Tag Upload form.
     *
     * @param FormBuilder $builder
     * @param array $options
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('attachment', 'file', array(
            'label' => 'tags.forms.upload_tags',
            'help' => 'tags.forms.csv_restrictions',
            'required' => true,
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_taguploadtype';
    }
}
