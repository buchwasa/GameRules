<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;

class EventListener implements Listener
{
	/** @var Loader */
	private Loader $loader;

	public function __construct(Loader $loader)
	{
		$this->loader = $loader;
	}

	public function handleReceive(DataPacketReceiveEvent $ev): void
	{
		$packet = $ev->getPacket();
		if ($packet instanceof SettingsCommandPacket) {
			$cmd = $packet->getCommand();
			$cmd = str_replace("/gamerule ", "", $cmd);
			$array = explode(" ", $cmd);

			$this->loader->addGameRule($array[0], $array[1] === true);
			$pk = new GameRulesChangedPacket();
			$pk->gameRules = $this->loader->getGameRuleList();
			$this->loader->getPlugin()->getServer()->broadcastPackets($this->loader->getPlugin()->getServer()->getOnlinePlayers(), [$pk]);
		}
	}

	public function handleSend(DataPacketSendEvent $ev): void
	{
		foreach ($ev->getPackets() as $packet) {
			if ($packet instanceof StartGamePacket) {
				$packet->gameRules = $this->loader->getGameRuleList();
			}
		}
	}
}
