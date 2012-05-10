<?php

namespace Knp\MediaBundle\Form\Type;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilder;

abstract class MediaType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $label = isset($options['image_label']) ? $options['image_label'] : 'Upload a file';

        $builder
            ->add('fileObject', null, array(
                'label' => $label,
                'required' => false,
            ))
        ;
    }

    /**
     * @return string
     */
    abstract public function getDataClass();

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => $this->getDataClass(),
            'image_label' => 'Upload a file',
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'knp_media';
    }
}