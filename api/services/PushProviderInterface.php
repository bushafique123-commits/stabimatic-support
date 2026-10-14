<?php

interface PushProviderInterface
{
    public function send(string $deviceToken, string $title, string $message, array $payload = []): bool;
}
