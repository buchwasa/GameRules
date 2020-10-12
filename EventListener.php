<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
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
			var_dump($gameRule);

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

	public function handleBurn(BlockBurnEvent $ev): void
	{
		if ($ev->getBlock()->getId() === VanillaBlocks::TNT()->getId() && !$this->loader->isGameRuleEnabled("tntexplodes")) {
			$ev->cancel();
		}
	}

	public function handleDamage(EntityDamageEvent $ev): void
	{
		if ($ev instanceof EntityDamageByEntityEvent && !$this->loader->isGameRuleEnabled("pvp")) {
			$ev->cancel();
		}
	}

	public function handleDeath(PlayerDeathEvent $ev): void
	{
		$ev->setKeepInventory($this->loader->isGameRuleEnabled("keepinventory"));
	}

	public function handleInteract(PlayerInteractEvent $ev): void
	{
		if ($ev->getBlock()->getId() === VanillaBlocks::TNT()->getId() && !$this->loader->isGameRuleEnabled("tntexplodes")) {
			$ev->cancel();
		}
	}

	public function handleRegen(EntityRegainHealthEvent $ev): void
	{
		if (!$this->loader->isGameRuleEnabled("naturalregeneration") && $ev->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
			$ev->cancel();
		}
	}
}
