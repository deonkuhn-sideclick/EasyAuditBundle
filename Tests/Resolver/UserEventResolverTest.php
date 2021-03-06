<?php

/*
 * This file is part of the XiideaEasyAuditBundle package.
 *
 * (c) Xiidea <http://www.xiidea.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xiidea\EasyAuditBundle\Tests\Resolver;

use PHPUnit\Framework\TestCase;
use Xiidea\EasyAuditBundle\Resolver\UserEventResolver;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Common\DummyToken;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\Basic;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\DummyAuthenticationFailureEvent;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\DummyFilterUserResponseEvent;
use Xiidea\EasyAuditBundle\Tests\Fixtures\Event\DummyUserEvent;
use Xiidea\EasyAuditBundle\Tests\Fixtures\ORM\UserEntity;

class UserEventResolverTest extends TestCase
{
    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $container;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $securityContext;

    /** @var  UserEventResolver */
    private $eventResolver;

    public function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->eventResolver = new UserEventResolver();
        $this->eventResolver->setContainer($this->container);

    }

    public function testIsAnInstanceOfEventResolverInterface()
    {
        $this->assertInstanceOf('Xiidea\EasyAuditBundle\Resolver\EventResolverInterface', $this->eventResolver);
    }

    /**
     * @expectedException \LogicException
     */
    public function testWithoutSecurityBundle()
    {
        $this->container->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('security.token_storage'))
            ->willReturn(false);

        $auditLog = $this->eventResolver->getEventLogInfo(new Basic(), 'security.interactive_login');

        $this->assertNull($auditLog);
    }

    public function testUnlistedEvent()
    {
        $auditLog = $this->eventResolver->getEventLogInfo(new Basic(), 'any_random_event');
        $this->assertEquals(array('type' => 'any_random_event', 'description' => 'any_random_event'), $auditLog);
    }

    public function testPasswordChangedEvent()
    {
        $event = new DummyFilterUserResponseEvent(new UserEntity());

        $auditLog = $this->eventResolver->getEventLogInfo($event, 'fos_user.change_password.edit.completed');

        $this->assertNotNull($auditLog);

        $this->assertEquals(array(
            'description' => "Password of user 'admin' Changed Successfully",
            'type' => "Password Changed"
        ), $auditLog);

    }

    public function testLoginEvent()
    {
        $this->initiateContainerWithSecurityContext();
        $event = new DummyFilterUserResponseEvent(new UserEntity());

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn(new DummyToken(new UserEntity()));

        $auditLog = $this->eventResolver->getEventLogInfo($event, 'security.interactive_login');

        $this->assertEquals(array (
            'description' => "User 'admin' Logged in Successfully",
            'type' => 'User Logged in',
        ),$auditLog);
    }

    public function testLoginUsingRememberMeService() {
        $event = new DummyUserEvent(new UserEntity());

        $auditLog = $this->eventResolver->getEventLogInfo($event, 'fos_user.security.implicit_login');

        $this->assertEquals(array (
            'description' => "User 'admin' Logged in Successfully using remember me service",
            'type' => 'User Logged in',
        ),$auditLog);
    }

    public function testAuthenticationFailureEvent()
    {
        $event = new DummyAuthenticationFailureEvent();

        $auditLog = $this->eventResolver->getEventLogInfo($event, 'security.authentication.failure');

        $this->assertNotNull($auditLog);

        $this->assertEquals(array(
            'description' => "Bad credentials Username: user",
            'type' => "Authentication Failed"
        ), $auditLog);

    }

    public function testUnknownUserEvent()
    {
        $this->assertIncompatibleEventObject('random.event');
    }

    public function testAuthenticationFailedCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('security.authentication.failure');
    }

    public function testImplicitLoginCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('fos_user.security.implicit_login');
    }

    public function testPasswordChangedCommandWithIncompatibleEventObject()
    {
        $this->assertIncompatibleEventObject('fos_user.change_password.edit.completed');
    }


    protected function initiateContainerWithSecurityContext()
    {
        $this->securityContext = $this
            ->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->at(0))
            ->method('has')
            ->with($this->equalTo('security.token_storage'))
            ->willReturn(true);

        $this->container->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('security.token_storage'))
            ->willReturn($this->securityContext);
    }

    /**
     * @param $randomEvent
     */
    public function assertIncompatibleEventObject($randomEvent)
    {
        $event = new Basic();
        $auditLog = $this->eventResolver->getEventLogInfo($event, $randomEvent);

        $this->assertNotNull($auditLog);

        $this->assertEquals(
            array(
                'description' => $randomEvent,
                'type' => $randomEvent
            ),
            $auditLog
        );
    }

    /**
     * @param $eventClass
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function initializeMockEvent($eventClass)
    {
        $event = $this
            ->getMockBuilder($eventClass)
            ->disableOriginalConstructor()
            ->getMock();

        return $event;
    }
}
 