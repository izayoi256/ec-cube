<?php

namespace Acme\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Form\Type\AddCartType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @Route("/products")
 */
class ProductOptionController
{
    private $title;

    public function __construct()
    {
        $this->title = '';
    }

    /**
     * 購入画面表示
     *
     * @Route("/detail/{id}", name="product_detail")
     * @Template("Product/detail.twig")
     *
     * @param Application $app
     * @param Request $request
     * @param integer $id
     * @return array
     */
    public function detail(Application $app, Request $request, $id)
    {
        $BaseInfo = $app['eccube.repository.base_info']->get();
        if ($BaseInfo->getNostockHidden() === Constant::ENABLED) {
            $app['orm.em']->getFilters()->enable('nostock_hidden');
        }

        /* @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->get($id);
        if (!$request->getSession()->has('_security_admin') && $Product->getStatus()->getId() !== 1) {
            throw new NotFoundHttpException();
        }
        if (count($Product->getProductClasses()) < 1) {
            throw new NotFoundHttpException();
        }

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $app['form.factory']->createNamedBuilder('', AddCartType::class, null, array(
            'product' => $Product,
            'id_add_product_id' => false,
        ));

        $event = new EventArgs(
            array(
                'builder' => $builder,
                'Product' => $Product,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE, $event);

        $builder->add('some_option', TextType::class, [
            'required' => false,
        ]);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $addCartData = $form->getData();
                if ($addCartData['mode'] === 'add_favorite') {
                    if ($app->isGranted('ROLE_USER')) {
                        $Customer = $app->user();
                        $app['eccube.repository.customer_favorite_product']->addFavorite($Customer, $Product);
                        $app['session']->getFlashBag()->set('product_detail.just_added_favorite', $Product->getId());

                        $event = new EventArgs(
                            array(
                                'form' => $form,
                                'Product' => $Product,
                            ),
                            $request
                        );
                        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_FAVORITE, $event);

                        if ($event->getResponse() !== null) {
                            return $event->getResponse();
                        }

                        return $app->redirect($app->url('product_detail', array('id' => $Product->getId())));
                    } else {
                        // 非会員の場合、ログイン画面を表示
                        //  ログイン後の画面遷移先を設定
                        $app->setLoginTargetPath($app->url('product_detail', array('id' => $Product->getId())));
                        $app['session']->getFlashBag()->set('eccube.add.favorite', true);
                        return $app->redirect($app->url('mypage_login'));
                    }
                } elseif ($addCartData['mode'] === 'add_cart') {

                    log_info('カート追加処理開始', array('product_id' => $Product->getId(), 'product_class_id' => $addCartData['product_class_id'], 'quantity' => $addCartData['quantity']));

                    try {
                        /** @var \Eccube\Entity\CartItem $CartItem */
                        $CartItem = $app['eccube.service.cart']->generateCartItem($addCartData['product_class_id']);
                        $CartItem->setQuantity($addCartData['quantity']);
                        $CartItem->setOption('some_option', $addCartData['some_option']);

                        $event = new EventArgs(
                            array(
                                'form' => $form,
                                'Product' => $Product,
                                'CartItem' => $CartItem,
                            ),
                            $request
                        );
                        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_ADD_CART, $event);

                        $app['eccube.service.cart']
                            ->addCartItem($CartItem)
                            ->save();
                    } catch (CartException $e) {
                        log_info('カート追加エラー', array($e->getMessage()));
                        $app->addRequestError($e->getMessage());
                    }

                    log_info('カート追加処理完了', array('product_id' => $Product->getId(), 'product_class_id' => $addCartData['product_class_id'], 'quantity' => $addCartData['quantity']));

                    $event = new EventArgs(
                        array(
                            'form' => $form,
                            'Product' => $Product,
                        ),
                        $request
                    );
                    $app['eccube.event.dispatcher']->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_COMPLETE, $event);

                    if ($event->getResponse() !== null) {
                        return $event->getResponse();
                    }

                    return $app->redirect($app->url('cart'));
                }
            }
        } else {
            $addFavorite = $app['session']->getFlashBag()->get('eccube.add.favorite');
            if (!empty($addFavorite)) {
                // お気に入り登録時にログインされていない場合、ログイン後にお気に入り追加処理を行う
                if ($app->isGranted('ROLE_USER')) {
                    $Customer = $app->user();
                    $app['eccube.repository.customer_favorite_product']->addFavorite($Customer, $Product);
                    $app['session']->getFlashBag()->set('product_detail.just_added_favorite', $Product->getId());
                }
            }
        }

        $is_favorite = false;
        if ($app->isGranted('ROLE_USER')) {
            $Customer = $app->user();
            $is_favorite = $app['eccube.repository.customer_favorite_product']->isFavorite($Customer, $Product);
        }

        return [
            'title' => $this->title,
            'subtitle' => $Product->getName(),
            'form' => $form->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
        ];
    }
}