<?php

namespace Eccube\Service\CartComparator\Strategy;

class SomeOptionStrategy implements CartComparatorStrategyInterface
{
    /**
     * @inheritdoc
     */
    public function compare($CartItem1, $CartItem2)
    {
        $option1 = $CartItem1->getOptions()->get('some_option');
        $option2 = $CartItem2->getOptions()->get('some_option');
        return
            $CartItem1->getClassId() == $CartItem2->getClassId() &&
            $CartItem1->getClassName() == $CartItem2->getClassName() &&
            $option1 == $option2;
    }
}
