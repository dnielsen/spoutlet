<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
        $builder->add('attachment', FileType::class, array(
            'label' => 'tags.forms.upload_tags',
            'required' => true,
        ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_spoutletbundle_taguploadtype';
    }
}
