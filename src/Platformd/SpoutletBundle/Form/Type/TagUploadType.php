<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TagUploadType extends AbstractType
{
    /**
     * Configures a Tag Upload form.
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('attachment', 'file', array(
            'label' => 'tags.forms.upload_tags',
            'required' => true,
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_taguploadtype';
    }
}
