<?php declare(strict_types = 1);

namespace Easir\KafkaMessengerBundle;

use Easir\KafkaMessengerBundle\DependencyInjection\KafkaMessengerExtension;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KafkaMessengerBundle extends Bundle
{
    public function getContainerExtension(): Extension
    {
        return new KafkaMessengerExtension();
    }
}
