<?php
declare(strict_types=1);

namespace libgamerules;

use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\plugin\Plugin;

class Loader
{
	/** @var Plugin */
	private Plugin $plugin;
	/** @var BoolGameRule[] */
	private array $cachedGameRules = [];
	/** @var bool[] */
	private array $lockedGameRules = [];

	public function __construct(Plugin $plugin)
	{
		$this->plugin = $plugin;
		$this->plugin->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this->plugin);
	}

	public function getPlugin(): Plugin
	{
		return $this->plugin;
	}

	public function lockGameRule(string $gameRule, bool $lockEnabled): void
	{
		$this->lockedGameRules[$gameRule] = true;
		$this->cachedGameRules[$gameRule] = new BoolGameRule($lockEnabled);
	}

	public function isGameRuleLocked(string $gameRule): bool
	{
		return isset($this->lockedGameRules[$gameRule]);
	}

	public function addGameRule(string $gameRule, bool $enabled): void
	{
		if (!$this->isGameRuleLocked($gameRule)) {
			if (isset($this->cachedGameRules[$gameRule])) {
				unset($this->cachedGameRules[$gameRule]);
			}

			$this->cachedGameRules[$gameRule] = new BoolGameRule($enabled);
		}
	}

	/**
	 * @return BoolGameRule[]
	 */
	public function getGameRuleList(): array
	{
		return $this->cachedGameRules;
	}

	public function isGameRuleEnabled(string $gameRule): bool
	{
		return isset($this->cachedGameRules[$gameRule]) && $this->cachedGameRules[$gameRule]->getValue() === true;
	}
}
