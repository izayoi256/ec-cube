<?php

namespace Eccube\Controller\Admin\Product;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductClassRegisterController extends AbstractController
{
    public function index(Application $app, Request $request, $id)
    {
        /** @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        /** @var \Eccube\Entity\BaseInfo $BaseInfo */
        $BaseInfo = $app['eccube.repository.base_info']->get();
        // 商品税率が設定されている場合、商品税率を項目に設定
        if ($BaseInfo->getOptionProductTaxRule() == Constant::ENABLED) {
            foreach ($Product->getProductClasses() as $ProductClass) {
                if ($ProductClass->getTaxRule() && !$ProductClass->getTaxRule()->getDelFlg()) {
                    $ProductClass->setTaxRate($ProductClass->getTaxRule()->getTaxRate());
                }
            }
        }

        $builder = $app['form.factory']->createBuilder('admin_product_class_register', $this->getData($Product), array(
            'validation_groups' => false,
        ));

        $form = $builder->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
        }

        return $app->render('Product/product_class_register.twig', array(
            'Product' => $Product,
            'form' => $form->createView(),
        ));
    }

    public function register(Application $app, Request $request, $id)
    {
        /** @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        $ProductClasses = $Product->getProductClasses();
        $BaseInfo = $app['eccube.repository.base_info']->get();

        $builder = $app['form.factory']->createBuilder('admin_product_class_register');
        $form = $builder->getForm();

        if ($request->getMethod() === 'POST') {

            $form->handleRequest($request);

            if ($form->isValid()) {

                /** @var \Eccube\Entity\ProductClass $ProductClass */
                foreach ($ProductClasses as $ProductClass) {
                    // 順番変更のため全削除とする
                    $ProductClass->setDelFlg(Constant::ENABLED);
                }

                /** @var \Eccube\Entity\ProductClass $ProductClass */
                foreach ($form['ProductClasses']->getData() as $ProductClass) {

                    $ProductClass->setProduct($Product);

                    /** @var \Eccube\Entity\ProductClass $ExistsProductClass */
                    $ExistsProductClass = $ProductClasses->filter(function (\Eccube\Entity\ProductClass $OriginalProductClass) use ($ProductClass) {
                        return
                            $ProductClass->getClassCategory1() === $OriginalProductClass->getClassCategory1() &&
                            $ProductClass->getClassCategory2() === $OriginalProductClass->getClassCategory2();
                    })->first();

                    if (!$ExistsProductClass) {

                        $ProductStock = new \Eccube\Entity\ProductStock();
                        $ProductStock->setProductClass($ProductClass);

                        $ProductClass
                            ->setProduct($Product)
                            ->setDelFlg(Constant::DISABLED)
                        ;

                        $ProductClasses->add($ProductClass);

                        $app['orm.em']->persist($ProductClass);
                        $app['orm.em']->persist($ProductStock);

                        // 商品税率が設定されている場合、商品税率をセット
                        if ($BaseInfo->getOptionProductTaxRule() == Constant::ENABLED) {

                            // 初期設定の税設定.
                            $TaxRule = $app['eccube.repository.tax_rule']->find(\Eccube\Entity\TaxRule::DEFAULT_TAX_RULE_ID);
                            // 初期税率設定の計算方法を設定する
                            $CalcRule = $TaxRule->getCalcRule();

                            if ($ProductClass->getTaxRate() !== false && $ProductClass !== null) {
                                $TaxRule = new \Eccube\Entity\TaxRule();
                                $TaxRule
                                    ->setProduct($Product)
                                    ->setProductClass($ProductClass)
                                    ->setCalcRule($CalcRule)
                                    ->setTaxRate($ProductClass->getTaxRate())
                                    ->setTaxAdjust(0)
                                    ->setApplyDate(new \DateTime())
                                    ->setDelFlg(Constant::DISABLED)
                                ;
                                $app['orm.em']->persist($TaxRule);
                            }
                        }
                    } else {

                        $this->copyProductClass($app, $ExistsProductClass, $ProductClass);
                        $ExistsProductClass->setDelFlg(Constant::DISABLED);
                        $ProductStock = $ExistsProductClass->getProductStock();
                    }

                    $stock = $ProductClass->getStockUnlimited() ?
                        null :
                        $ProductClass->getStock();
                    $ProductStock->setStock($stock);
                }

                $app['orm.em']->flush();
                $app->addSuccess('admin.product.product_class.save.complete', 'admin');
                return $app->redirect($app->url('admin_product_class', array('id' => $id)));
            }
        }

        return $app->render('Product/product_class_register.twig', array(
            'Product' => $Product,
            'form' => $form->createView(),
        ));
    }

    public function delete(Application $app, Request $request, $id)
    {
        $this->isTokenValid($app);

        /** @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->find($id);

        if (!$Product) {
            throw new NotFoundHttpException();
        }

        foreach ($Product->getProductClasses() as $ProductClass) {
            $ProductClass->setDelFlg(Constant::ENABLED);
        }

        /* @var $softDeleteFilter \Eccube\Doctrine\Filter\SoftDeleteFilter */
        $softDeleteFilter = $app['orm.em']->getFilters()->getFilter('soft_delete');
        $excludes = $softDeleteFilter->getExcludes();
        $softDeleteFilter->setExcludes(array(
            'Eccube\Entity\ProductClass'
        ));

        $DefaultProductClass = $app['eccube.repository.product_class']->findOneBy(array('Product' => $Product, 'ClassCategory1' => null, 'ClassCategory2' => null));
        $DefaultProductClass->setDelFlg(Constant::DISABLED);

        $app['orm.em']->flush();
        $softDeleteFilter->setExcludes($excludes);

        $app->addSuccess('admin.product.product_class.delete.complete', 'admin');

        return $app->redirect($app->url('admin_product_class', array('id' => $id)));
    }

    /**
     * @param \Eccube\Entity\Product $Product
     * @return array
     */
    protected function getData(\Eccube\Entity\Product $Product)
    {
        $hasProductClass = $Product->hasProductClass();

        return array(
            'ProductClasses' => $hasProductClass ?
                $Product->getProductClasses() :
                array(),
            'ClassName1' => $hasProductClass ?
                $Product->getProductClasses()->first()->getClassCategory1()->getClassName() :
                null,
            'ClassName2' => $hasProductClass && $Product->getProductClasses()->first()->getClassCategory2()?
                $Product->getProductClasses()->first()->getClassCategory2()->getClassName() :
                null,
        );
    }

    /**
     * @param Application $app
     * @param \Eccube\Entity\ProductClass $dest
     * @param \Eccube\Entity\ProductClass $src
     */
    protected function copyProductClass(Application $app, \Eccube\Entity\ProductClass $dest, \Eccube\Entity\ProductClass $src)
    {
        $dest
            ->setDeliveryDate($src->getDeliveryDate())
            ->setProduct($src->getProduct())
            ->setProductType($src->getProductType())
            ->setCode($src->getCode())
            ->setStock($src->getStock())
            ->setStockUnlimited($src->getStockUnlimited())
            ->setSaleLimit($src->getSaleLimit())
            ->setPrice01($src->getPrice01())
            ->setPrice02($src->getPrice02())
            ->setDeliveryFee($src->getDeliveryFee());

        // 個別消費税
        $BaseInfo = $app['eccube.repository.base_info']->get();
        if ($BaseInfo->getOptionProductTaxRule() == Constant::ENABLED) {
            if ($src->getTaxRate() !== false && $src->getTaxRate() !== null) {
                $dest->setTaxRate($src->getTaxRate());
                if ($dest->getTaxRule()) {
                    $dest->getTaxRule()->setTaxRate($src->getTaxRate());
                    $dest->getTaxRule()->setDelFlg(Constant::DISABLED);
                } else {
                    $taxrule = $app['eccube.repository.tax_rule']->newTaxRule();
                    $taxrule->setTaxRate($src->getTaxRate());
                    $taxrule->setApplyDate(new \DateTime());
                    $taxrule->setProduct($dest->getProduct());
                    $taxrule->setProductClass($dest);
                    $dest->setTaxRule($taxrule);
                }
            } else {
                if ($dest->getTaxRule()) {
                    $dest->getTaxRule()->setDelFlg(Constant::ENABLED);
                }
            }
        }
    }
}
