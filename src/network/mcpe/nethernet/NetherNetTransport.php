<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\nethernet;

use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\NetworkInterfaceStartException;
use pocketmine\network\Transport;
use pocketmine\Server;
use pocketmine\YmlServerProperties as Yml;

final class NetherNetTransport implements Transport{

	public const NAME = "nethernet";

	public function __construct(
		private Server $server
	){}

	public function createInterfaces(
		PacketBroadcaster $packetBroadcaster,
		EntityEventBroadcaster $entityEventBroadcaster,
		TypeConverter $typeConverter
	) : array{
		$configGroup = $this->server->getConfigGroup();

		$keyFile = $configGroup->getPropertyString(Yml::TRANSPORT_NETHERNET_KEY_FILE, "nethernet.key");
		try{
			$iceConfig = NetherNetIceConfiguration::parse(
				$configGroup->getProperty(Yml::TRANSPORT_NETHERNET_ICE_SERVERS),
				$configGroup->getProperty(Yml::TRANSPORT_NETHERNET_PORT_RANGE),
				$configGroup->getPropertyBool(Yml::TRANSPORT_NETHERNET_ICE_UDP_MUX, false)
			);
			$reverseProxyNetworks = $configGroup->getPropertyBool(Yml::TRANSPORT_NETHERNET_REVERSE_PROXY_ENABLED, false)
				? NetherNetThread::parseReverseProxyNetworks($configGroup->getProperty(Yml::TRANSPORT_NETHERNET_REVERSE_PROXY_TRUSTED_IPS))
				: null;
		}catch(\InvalidArgumentException $e){
			throw new NetworkInterfaceStartException("Invalid NetherNet settings in pocketmine.yml: " . $e->getMessage(), 0, $e);
		}
		if($iceConfig->isUdpMux() && $iceConfig->getPortRangeBegin() === null){
			$this->server->getLogger()->warning($this->server->getLanguage()->translate(KnownTranslationFactory::pocketmine_server_nethernet_udpMuxWithoutPortRange(Yml::TRANSPORT_NETHERNET_ICE_UDP_MUX)));
		}

		return [new NetherNetInterface($this->server, $this->server->getIp(), $this->server->getPort(), $keyFile, $iceConfig, $reverseProxyNetworks, $packetBroadcaster, $entityEventBroadcaster, $typeConverter)];
	}
}
