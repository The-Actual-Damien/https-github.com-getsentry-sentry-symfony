<?php

declare(strict_types=1);

namespace Sentry\SentryBundle\DependencyInjection\Compiler;

use Sentry\SentryBundle\Tracing\Cache\TraceableCacheAdapter;
use Sentry\SentryBundle\Tracing\Cache\TraceableTagAwareCacheAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CacheTracingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$this->isTracingEnabled($container)) {
            return;
        }

        foreach ($container->findTaggedServiceIds('cache.pool') as $serviceId => $tags) {
            $cachePoolDefinition = $container->getDefinition($serviceId);

            if ($cachePoolDefinition->isAbstract()) {
                continue;
            }

            if (is_subclass_of($cachePoolDefinition->getClass(), TagAwareAdapterInterface::class)) {
                $traceableCachePoolDefinition = new ChildDefinition(TraceableTagAwareCacheAdapter::class);
            } else {
                $traceableCachePoolDefinition = new ChildDefinition(TraceableCacheAdapter::class);
            }

            $traceableCachePoolDefinition->setDecoratedService($serviceId);
            $traceableCachePoolDefinition->replaceArgument(1, new Reference($serviceId . '.traceable.inner'));

            $container->setDefinition($serviceId . '.traceable', $traceableCachePoolDefinition);
        }
    }

    private function isTracingEnabled(ContainerBuilder $container): bool
    {
        return $container->getParameter('sentry.tracing.enabled') && $container->getParameter('sentry.tracing.cache.enabled');
    }
}
