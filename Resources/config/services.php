<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Noxlogic\RateLimitBundle\EventListener\HeaderModificationListener;
use Noxlogic\RateLimitBundle\EventListener\RateLimitAnnotationListener;
use Noxlogic\RateLimitBundle\Service\RateLimitService;
use Noxlogic\RateLimitBundle\Util\PathLimitProcessor;

return function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('noxlogic_rate_limit.storage')
        ->class('%noxlogic_rate_limit.storage.class%')
        ->args([service('snc_redis.default_client')]);

    $services->set('noxlogic_rate_limit.rate_limit_service')
        ->class(RateLimitService::class)
        ->call('setStorage', [service('noxlogic_rate_limit.storage')]);

    $services->set('noxlogic_rate_limit.path_limit_processor')
        ->class(PathLimitProcessor::class)
        ->args(['%noxlogic_rate_limit.path_limits%']);

    $services->set('noxlogic_rate_limit.rate_limit_annotation_listener')
        ->class(RateLimitAnnotationListener::class)
        ->args([
            service('event_dispatcher'),
            service('noxlogic_rate_limit.rate_limit_service'),
            service('noxlogic_rate_limit.path_limit_processor'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.controller',
            'method' => 'onKernelController',
            'priority' => -10,
        ])
        ->call('setParameter', ['enabled', '%noxlogic_rate_limit.enabled%'])
        ->call('setParameter', ['rate_response_code', '%noxlogic_rate_limit.rate_response_code%'])
        ->call('setParameter', ['rate_response_message', '%noxlogic_rate_limit.rate_response_message%'])
        ->call('setParameter', ['rate_response_exception', '%noxlogic_rate_limit.rate_response_exception%']);

    $services->set('noxlogic_rate_limit.header_modification_listener')
        ->class(HeaderModificationListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'kernel.response',
            'method' => 'onKernelResponse',
            'priority' => 0,
        ])
        ->call('setParameter', ['rate_response_code', '%noxlogic_rate_limit.rate_response_code%'])
        ->call('setParameter', ['display_headers', '%noxlogic_rate_limit.display_headers%'])
        ->call('setParameter', ['header_limit_name', '%noxlogic_rate_limit.headers.limit.name%'])
        ->call('setParameter', ['header_remaining_name', '%noxlogic_rate_limit.headers.remaining.name%'])
        ->call('setParameter', ['header_reset_name', '%noxlogic_rate_limit.headers.reset.name%']);
};
