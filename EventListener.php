<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\event\entity\EntityRegainHealthEvent;
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
			$gameRule = explode(" ", $packet->getCommand());
			if ($gameRule[0] !== "/gamerule") {
				return;
			}

			if (!$this->loader->isGameRuleLocked($gameRule[1])) {
				$this->loader->addGameRule($gameRule[1], $gameRule[2] === "true");
				$pk = new GameRulesChangedPacket();
				$pk->gameRules = $this->loader->getGameRuleArray($gameRule[1]);
				$this->loader->getPlugin()->getServer()->broadcastPackets($this->loader->getPlugin()->getServer()->getOnlinePlayers(), [$pk]);
			}
		}
	}

	public function handleGameRuleChange(GameRuleChangedEvent $ev): void
	{
		if ($ev->getGameRule() === "dodaylightcycle") {
			foreach ($this->loader->getPlugin()->getServer()->getWorldManager()->getWorlds() as $world) {
				if ($ev->isGameRuleEnabled()) {
					$world->startTime();
				} else {
					$world->stopTime();
				}
			}
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

	public function handleRegen(EntityRegainHealthEvent $ev): void
	{
		if (!$this->loader->isGameRuleEnabled("naturalregeneration") && $ev->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
			$ev->cancel();
		}
	}
}
