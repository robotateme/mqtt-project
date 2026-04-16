<?php

namespace Core\Application\Packets;

interface PacketStoragePort
{
    /**
     * @param array<int, array<string, mixed>> $packets
     */
    public function store(array $packets): void;
}
