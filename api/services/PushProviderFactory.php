<?php

final class PushProviderFactory
{
    public static function make(): PushProviderInterface
    {
        if (strtolower((string) config('push.provider', 'log')) === 'fcm') {
            return new FcmPushProvider();
        }

        return new LogPushProvider();
    }
}
