<?php

namespace Sentry\SentryBundle\Test;

use PHPUnit\Framework\TestCase;
use Sentry\SentryBundle\DependencyInjection\SentryExtension;
use Sentry\SentryBundle\EventListener\ConsoleListener;
use Sentry\SentryBundle\EventListener\RequestListener;
use Sentry\SentryBundle\EventListener\SubRequestListener;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

class SentryBundleTest extends TestCase
{
    public function testContainerHasConsoleListenerConfiguredCorrectly(): void
    {
        $container = $this->getContainer();

        $consoleListener = $container->getDefinition(ConsoleListener::class);

        $expectedTag = [
            'kernel.event_listener' => [
                [
                    'event' => ConsoleEvents::COMMAND,
                    'method' => 'onConsoleCommand',
                    'priority' => '%sentry.listener_priorities.console%',
                ],
            ],
        ];

        $this->assertSame($expectedTag, $consoleListener->getTags());
    }

    public function testContainerHasRequestListenerConfiguredCorrectly(): void
    {
        $container = $this->getContainer();

        $consoleListener = $container->getDefinition(RequestListener::class);

        $expectedTag = [
            'kernel.event_listener' => [
                [
                    'event' => KernelEvents::REQUEST,
                    'method' => 'onKernelRequest',
                    'priority' => '%sentry.listener_priorities.request%',
                ],
                [
                    'event' => KernelEvents::CONTROLLER,
                    'method' => 'onKernelController',
                    'priority' => '%sentry.listener_priorities.request%',
                ],
            ],
        ];

        $this->assertSame($expectedTag, $consoleListener->getTags());
    }

    public function testContainerHasSubRequestListenerConfiguredCorrectly(): void
    {
        $container = $this->getContainer();

        $consoleListener = $container->getDefinition(SubRequestListener::class);

        $expectedTag = [
            'kernel.event_listener' => [
                [
                    'event' => KernelEvents::REQUEST,
                    'method' => 'onKernelRequest',
                    'priority' => '%sentry.listener_priorities.sub_request%',
                ],
                [
                    'event' => KernelEvents::FINISH_REQUEST,
                    'method' => 'onKernelFinishRequest',
                    'priority' => '%sentry.listener_priorities.sub_request%',
                ],
            ],
        ];

        $this->assertSame($expectedTag, $consoleListener->getTags());
    }

    private function getContainer(array $configuration = []): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('kernel.root_dir', 'kernel/root');
        if (method_exists(Kernel::class, 'getProjectDir')) {
            $containerBuilder->setParameter('kernel.project_dir', '/dir/project/root');
        }

        $containerBuilder->setParameter('kernel.environment', 'test');
        $containerBuilder->set('event_dispatcher', $this->prophesize(EventDispatcherInterface::class)->reveal());

        $extension = new SentryExtension();
        $extension->load(['sentry' => $configuration], $containerBuilder);

        return $containerBuilder;
    }
}