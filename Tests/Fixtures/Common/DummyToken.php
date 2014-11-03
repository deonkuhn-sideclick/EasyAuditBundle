<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Tests\Fixtures\Common;

use Xiidea\EasyAuditBundle\Tests\Fixtures\ORM\UserEntity;

class DummyToken
{
    private $user;

    public function __construct($user = "user") {
        $this->user = $user;
    }

    public function getUser() {
        return $this->user;
    }
} 