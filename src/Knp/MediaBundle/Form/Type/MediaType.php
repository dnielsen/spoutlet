<?php

namespace Knp\MediaBundle\Form\Type;

use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

abstract class MediaType extends AbstractType
{
    private $helpMessage;

    public function buildForm(FormBuilder $builder, array $options)
    {
        $label = isset($options['image_label']) ? $options['image_label'] : 'Upload a file';

        if (isset($options['image_help'])) {
            $this->helpMessage = $options['image_help'];
        }

        $builder
            ->add('fileObject', 'file', array(
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
            'image_help' => null,
        );
    }

    /**
     * Set the "help" message on the form view
     *
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     */
    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        if ($this->helpMessage) {
            $view['fileObject']->set('help', $this->helpMessage);
        }
    }


    /**
     * @return string
     */
    public function getName()
    {
        return 'knp_media';
    }
}