<?php

namespace Corp\EiisBundle\Admin;

use Corp\EiisBundle\Entity\EiisLog;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class EiisLogAdmin extends AbstractAdmin
{
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'dateCreated'
    );
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('systemObjectCode')
            ->add('eiisId')
            ->add('dateCreated')
            ->add('type', 'doctrine_orm_choice',[], ChoiceType::class, [
                'choices'=>array_flip(EiisLog::$typeString)
            ])
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
//            ->add('id')
            ->add('systemObjectCode')
            ->add('eiisId')
			->add('logHistory','1234',['template'=>'CorpEiisBundle:Admin:logHistory.html.twig'])
            ->add('dateCreated')
//            ->add('_action', null, [
//                'actions' => [
//                    'show' => []
//                ],
//            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {

    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('systemObjectCode')
            ->add('eiisId')
            ->add('dateCreated')
        ;
    }
}
