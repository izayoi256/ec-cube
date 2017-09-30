<?php

namespace Eccube\Form\Type\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Repository\ClassNameRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductClassRegisterType extends AbstractType
{
    /**
     * @var ClassNameRepository
     */
    private $classNameRepository;

    /**
     * @param EntityRepository $classNameRepository
     */
    public function __construct(EntityRepository $classNameRepository)
    {
        $this->classNameRepository = $classNameRepository;
    }

    /**
     * @return ClassNameRepository
     */
    public function getClassNameRepository()
    {
        return $this->classNameRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $self = $this;
        $classNameRepository = $this->getClassNameRepository();

        $builder
            ->add('ClassName1', 'entity', array(
                'label' => '規格1',
                'empty_value' => '規格1を選択',
                'class' => 'Eccube\Entity\ClassName',
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('ClassName2', 'entity', array(
                'label' => '規格2',
                'empty_value' => '規格2を選択',
                'class' => 'Eccube\Entity\ClassName',
                'required' => false,
            ))
            ->add('ProductClasses', 'collection', array(
                'type' => 'admin_product_class',
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($self) {

            $form = $event->getForm();
            $data = $event->getData();

            if ($data) {

                /** @var \Eccube\Entity\ClassName $ClassName1 */
                $ClassName1 = $data['ClassName1'];

                /** @var \Eccube\Entity\ClassName $ClassName2 */
                $ClassName2 = $data['ClassName2'];

                $self->addClassCategory($form, $ClassName1, $ClassName2);

                if (isset($data['ProductClasses'])) {

                    /** @var \Eccube\Entity\ProductClass[] $ProductClasses */
                    $ProductClasses = $data['ProductClasses'];
                    if ($ProductClasses instanceof \Doctrine\Common\Collections\Collection) {
                        $ProductClasses = $ProductClasses->toArray();
                    }

                    usort($ProductClasses, function ($PC1, $PC2) {

                        /** @var \Eccube\Entity\ProductClass $PC1 */
                        $pc1cc1_id = $PC1->getClassCategory1() ?
                            $PC1->getClassCategory1()->getId() :
                            null;
                        $pc1cc2_id = $PC1->getClassCategory2() ?
                            $PC1->getClassCategory2()->getId() :
                            null;

                        /** @var \Eccube\Entity\ProductClass $PC2 */
                        $pc2cc1_id = $PC2->getClassCategory1() ?
                            $PC2->getClassCategory1()->getId() :
                            null;
                        $pc2cc2_id = $PC2->getClassCategory2() ?
                            $PC2->getClassCategory2()->getId() :
                            null;

                        if ($pc1cc1_id === $pc2cc1_id && $pc1cc2_id === $pc2cc2_id) {
                            return 0;
                        }

                        return ($pc1cc1_id < $pc2cc1_id || ($pc1cc1_id === $pc2cc1_id && $pc1cc2_id < $pc2cc2_id)) ?
                            -1 :
                            1;
                    });

                    $form['ProductClasses']->setData($ProductClasses);
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($self, $classNameRepository) {

            $form = $event->getForm();
            $data = $event->getData();

            /** @var \Eccube\Entity\ClassName $ClassName1 */
            $ClassName1 = isset($data['ClassName1']) && strlen($data['ClassName1']) ?
                $classNameRepository->find($data['ClassName1']) :
                null;

            /** @var \Eccube\Entity\ClassName $ClassName2 */
            $ClassName2 = isset($data['ClassName2']) && strlen($data['ClassName2']) ?
                $classNameRepository->find($data['ClassName2']) :
                null;

            if ($ClassName1 && ($ClassName1 === $ClassName2)) {
                $form['ClassName2']->addError(new FormError('規格1と規格2は、同じ値を使用できません。'));
            } else {
                $self->addClassCategory($form, $ClassName1, $ClassName2);
            }

            if (isset($data['ProductClasses']) && is_array($data['ProductClasses'])) {

                usort($data['ProductClasses'], function ($pc1, $pc2) {

                    $pc1cc1_id = $pc1['ClassCategory1'];
                    $pc1cc2_id = $pc1['ClassCategory2'];

                    $pc2cc1_id = $pc2['ClassCategory1'];
                    $pc2cc2_id = $pc2['ClassCategory2'];

                    if ($pc1cc1_id === $pc2cc1_id && $pc1cc2_id === $pc2cc2_id) {
                        return 0;
                    }

                    return ($pc1cc1_id < $pc2cc1_id || ($pc1cc1_id === $pc2cc1_id && $pc1cc2_id < $pc2cc2_id)) ?
                        -1 :
                        1;
                });
            }

            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {

            $form = $event->getForm();
            $data = $event->getData();

            if ($data['ClassName1'] && $data['ClassName1'] === $data['ClassName2']) {
                $form['ClassName2']->addError(new FormError('規格1と規格2は、同じ値を使用できません。'));
            }
        });
    }

    /**
     * @param FormInterface $form
     * @param \Eccube\Entity\ClassName|null $ClassName1
     * @param \Eccube\Entity\ClassName|null $ClassName2
     */
    protected function addClassCategory(FormInterface $form, \Eccube\Entity\ClassName $ClassName1 = null, \Eccube\Entity\ClassName $ClassName2 = null)
    {
        if ($ClassName1) {

            $form->add('ClassCategory1', 'entity', array(
                'label' => '規格カテゴリ1',
                'class' =>'Eccube\Entity\ClassCategory',
                'required' => false,
                'empty_value' => false,
                'choices' => $ClassName1->getClassCategories(),
                'multiple' => true,
            ));

            if ($ClassName2) {

                $form->add('ClassCategory2', 'entity', array(
                    'label' => '規格カテゴリ2',
                    'class' =>'Eccube\Entity\ClassCategory',
                    'required' => false,
                    'empty_value' => false,
                    'choices' => $ClassName2->getClassCategories(),
                    'multiple' => true,
                ));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'allow_extra_fields' => true,
            'Product' => null,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'admin_product_class_register';
    }
}
