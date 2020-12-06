<?php
declare(strict_types=1);

namespace gamerules;

use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\player\Player;

class EventListener implements Listener
{
	/** @var Loader */
	private Loader $plugin;

	public function __construct(Loader $plugin)
	{
		$this->plugin = $plugin;
	}

	public function handleReceive(DataPacketReceiveEvent $ev): void
	{
		$packet = $ev->getPacket();
		if ($packet instanceof SettingsCommandPacket) {
			$gameRule = explode(" ", $packet->getCommand());
			if ($gameRule[0] !== "/gamerule") {
				return;
			}

			if (!$this->plugin->isGameRuleLocked($gameRule[1])) {
				$this->plugin->addGameRule($gameRule[1], $gameRule[2] === "true", $this->plugin->getServer(), true);
			}
		}
	}

	public function handleGameRuleChange(GameRuleChangedEvent $ev): void
	{
		if ($ev->getGameRule() === "dodaylightcycle") {
			foreach ($this->plugin->getServer()->getWorldManager()->getWorlds() as $world) {
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
				$packet->gameRules = $this->plugin->getGameRuleList();
			}
		}
	}

	public function handleBreak(BlockBreakEvent $ev): void
	{
		if(!$this->plugin->isGameRuleEnabled("dotiledrops")) {
			$ev->setDrops([]);
		}
	}

	public function handleBurn(BlockBurnEvent $ev): void
	{
		if ($ev->getBlock()->getId() === VanillaBlocks::TNT()->getId() && !$this->plugin->isGameRuleEnabled("tntexplodes")) {
			$ev->cancel();
		}
	}

	public function handleDamage(EntityDamageEvent $ev): void
	{
		if ($ev instanceof EntityDamageByEntityEvent && !$this->plugin->isGameRuleEnabled("pvp")) {
			$ev->cancel();
		}
	}

	public function handleDeath(PlayerDeathEvent $ev): void
	{
		$ev->setKeepInventory($this->plugin->isGameRuleEnabled("keepinventory"));
	}

	public function handleEntityDeath(EntityDeathEvent $ev): void
	{
		if (!($ev->getEntity() instanceof Player) && !$this->plugin->isGameRuleEnabled("domobloot")) {
			$ev->setDrops([]);
		}
	}

	public function handleInteract(PlayerInteractEvent $ev): void
	{
		if ($ev->getBlock()->getId() === VanillaBlocks::TNT()->getId() && !$this->plugin->isGameRuleEnabled("tntexplodes")) {
			$ev->cancel();
		}
	}

	public function handleRegen(EntityRegainHealthEvent $ev): void
	{
		if (!$this->plugin->isGameRuleEnabled("naturalregeneration") && $ev->getRegainReason() === EntityRegainHealthEvent::CAUSE_SATURATION) {
			$ev->cancel();
		}
	}
}
