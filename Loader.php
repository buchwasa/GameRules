<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\SettingsCommandPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\plugin\Plugin;

class Loader implements Listener
{
	private Plugin $plugin;
	/** @var array */
	private array $cachedGameRules = [];

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function handleReceive(DataPacketReceiveEvent $ev): void
	{
		$packet = $ev->getPacket();
		if ($packet instanceof SettingsCommandPacket) {
			$cmd = $packet->getCommand();
			$cmd = str_replace("/gamerule ", "", $cmd);
			$array = explode(" ", $cmd);

			$this->cachedGameRules[$array[0]] = new BoolGameRule($array[1] === "true");
			$pk = new GameRulesChangedPacket();
			$pk->gameRules = $this->cachedGameRules;
			$this->plugin->getServer()->broadcastPackets($this->plugin->getServer()->getOnlinePlayers(), [$pk]);
		}
	}

	public function handleSend(DataPacketSendEvent $ev): void
	{
		foreach ($ev->getPackets() as $packet) {
			if ($packet instanceof StartGamePacket) {
				$packet->gameRules = $this->cachedGameRules;
			}
		}
	}
}
