<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Model\ExportQueryManager;

class ExportQueryType extends AbstractType
{
    protected $exportQueryManager;

    public function __construct(ExportQueryManager $exportQueryManager)
    {
        $this->exportQueryManager = $exportQueryManager;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->add('reportTypes', 'choice', array(
            'label'     => 'Report',
            'help'      => 'Please be aware that running certain reports may take a long time depending on what is being asked for.',
            'choices'   => $this->getReportTypes(),
            'required'  => false,
        ));

        $builder->add('fromDate', 'datetime', array(
            'label'     => 'Start Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));

        $builder->add('thruDate', 'datetime', array(
            'label'     => 'End Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));
    }

    public function getName()
    {
        return 'platformd_export_query_type';
    }

    private function getReportTypes()
    {
        $types = array();
        foreach ($this->exportQueryManager->getReportTypes() as $reportType) {
            $types[$reportType] = $reportType;
        }
        return $types;
    }
}
