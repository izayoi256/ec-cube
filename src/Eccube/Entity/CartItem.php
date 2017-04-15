<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Eccube\Entity;

class CartItem extends \Eccube\Entity\AbstractEntity
{

    private $class_name;
    private $class_id;
    private $price;
    private $quantity;
    private $object;

    /** @var \Doctrine\Common\Collections\ArrayCollection */
    private $options;

    public function __construct()
    {
        $this->options = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __sleep()
    {
        return array('class_name', 'class_id', 'price', 'quantity', 'options');
    }

    /**
     * @param  string   $class_name
     * @return CartItem
     */
    public function setClassName($class_name)
    {
        $this->class_name = $class_name;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     * @param  string   $class_id
     * @return CartItem
     */
    public function setClassId($class_id)
    {
        $this->class_id = $class_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->class_id;
    }

    /**
     * @param  integer  $price
     * @return CartItem
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param  integer  $quantity
     * @return CartItem
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return integer
     */
    public function getTotalPrice()
    {
        return $this->getPrice() * $this->getQuantity();
    }

    /**
     * @param  object   $object
     * @return CartItem
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Add Option
     *
     * @param integer|string $key
     * @param object $option
     * @return $this
     */
    public function setOption($key, $option)
    {
        $this->options->set($key, $option);

        return $this;
    }

    /**
     * Remove Option Element
     *
     * @param object $option
     * @return $this
     */
    public function removeOptionElement($option)
    {
        $this->options->removeElement($option);

        return $this;
    }

    /**
     * Remove Option
     *
     * @param integer|string $key
     * @return $this
     */
    public function removeOption($key)
    {
        $this->options->remove($key);

        return $this;
    }
}
