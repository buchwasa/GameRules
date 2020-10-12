<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\plugin\Plugin;

class Loader implements Listener
{
	/** @var Plugin */
	private Plugin $plugin;
	/** @var BoolGameRule[] */
	private array $cachedGameRules = [];

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->plugin->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this->plugin);
	}

	public function getPlugin(): Plugin
	{
		return $this->plugin;
	}

	public function addGameRule(string $gameRule, bool $enabled): void
	{
		$this->cachedGameRules[$gameRule] = new BoolGameRule($enabled);
	}

	/**
	 * @return BoolGameRule[]
	 */
	public function getGameRuleList(): array
	{
		return $this->cachedGameRules;
	}
}
