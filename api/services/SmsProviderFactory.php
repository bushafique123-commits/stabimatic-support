<?php

final class SmsProviderFactory
{
    public static function make(): SmsProviderInterface
    {
        if (strtolower((string) config('sms.provider', 'dummy')) === 'sendpk') {
            return new SendPkSmsProvider();
        }

        return new DummySmsProvider();
    }
}
