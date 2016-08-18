<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2016 LOCKON CO.,LTD. All Rights Reserved.
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


namespace Eccube\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RenderEvent
 *
 * @package Eccube\Event
 */
class RenderEvent extends Event
{
    /**
     * @var string
     */
    private $view;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @var null|Response
     */
    private $response;

    /**
     * RenderEvent constructor.
     *
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     */
    public function __construct($view, array $parameters = array(), Response $response = null)
    {
        $this->view = $view;
        $this->parameters = $parameters;
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param string $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return null|Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param null|Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
