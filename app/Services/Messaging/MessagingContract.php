<?php

namespace App\Services\Messaging;

interface MessagingContract
{
    public function publish(array $data): void;
}
