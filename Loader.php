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

	/**
	 * Locks a gamerule to a state that cannot be changed.
	 *
	 * @param string $gameRule
	 * @param bool $lockEnabled
	 */
	public function lockGameRule(string $gameRule, bool $lockEnabled): void
	{
		$this->addGameRule($gameRule, $lockEnabled);
		$this->lockedGameRules[$gameRule] = true;
	}

	public function isGameRuleLocked(string $gameRule): bool
	{
		return isset($this->lockedGameRules[$gameRule]);
	}

	/**
	 * Adds a gamerule to the cache
	 *
	 * @param string $gameRule
	 * @param bool $enabled
	 */
	public function addGameRule(string $gameRule, bool $enabled): void
	{
		if (!$this->isGameRuleLocked($gameRule)) {
			$this->cachedGameRules[$gameRule] = new BoolGameRule($enabled);
		}
	}

	/**
	 * Gets a gamerule from the cache, returns null if not in cache.
	 *
	 * @param string $gameRule
	 * @return BoolGameRule|null
	 */
	public function getGameRule(string $gameRule): ?BoolGameRule
	{
		return isset($this->cachedGameRules[$gameRule]) ? $this->cachedGameRules[$gameRule] : null;
	}

	/**
	 * Gets the gamerule and returns it as an array for sending.
	 *
	 * @param string $gameRule
	 * @return null[]|BoolGameRule[]|null
	 */
	public function getGameRuleArray(string $gameRule): ?array
	{
		return isset($this->cachedGameRules[$gameRule]) ? [$gameRule => $this->getGameRule($gameRule)] : null;
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
