<?php
declare(strict_types=1);

namespace gamerules;

use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Loader extends PluginBase
{
	/** @var BoolGameRule[] */
	private array $cachedGameRules = [];
	/** @var bool[] */
	private array $lockedGameRules = [];

	protected function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$disabledGameRules = [
			"dofiretick", "domobspawning", "mobgriefing",
			"doweathercycle", "commandblocksenabled", "doentitydrops",
			"spawnradius", "randomtickspeed"
		];
		foreach ($disabledGameRules as $disabledGameRule) {
			$this->lockGameRule($disabledGameRule, false);
		}

		$defaultGameRules = [
			"pvp", "tntexplodes", "domobloot",
			"naturalregeneration", "dotiledrops", "dodaylightcycle"
		];
		foreach ($defaultGameRules as $defaultGameRule) {
			$this->addGameRule($defaultGameRule, true);
		}
	}

	/**
	 * Locks a gamerule to a state that cannot be changed by a player.
	 *
	 * @param string $gameRule
	 * @param bool $lockEnabled
	 * @param Server|null $server - optional, only if $needsUpdated = true
	 * @param bool $needsUpdated
	 */
	public function lockGameRule(string $gameRule, bool $lockEnabled, ?Server $server = null, bool $needsUpdated = false): void
	{
		if ($this->isGameRuleLocked($gameRule)) {
			unset($this->lockedGameRules[mb_strtolower($gameRule)]);
		}
		$this->addGameRule($gameRule, $lockEnabled, $server, $needsUpdated);
		$this->lockedGameRules[mb_strtolower($gameRule)] = true;
	}

	public function isGameRuleLocked(string $gameRule): bool
	{
		return isset($this->lockedGameRules[mb_strtolower($gameRule)]);
	}

	/**
	 * Adds a gamerule to the cache
	 *
	 * @param string $gameRule
	 * @param bool $enabled
	 * @param Server|null $server Required only if $needsUpdate = true
	 * @param bool $needsUpdated
	 */
	public function addGameRule(string $gameRule, bool $enabled, ?Server $server = null, bool $needsUpdated = false): void
	{
		if (!$this->isGameRuleLocked($gameRule)) {
			$ev = new GameRuleChangedEvent(mb_strtolower($gameRule), $enabled);
			$ev->call();
			if (!$ev->isCancelled()) {
				$this->cachedGameRules[$ev->getGameRule()] = new BoolGameRule($ev->isGameRuleEnabled());
				if ($needsUpdated && $server !== null && $this->getGameRuleAsArray($ev->getGameRule()) !== null) {
					$pk = new GameRulesChangedPacket();
					$pk->gameRules = $this->getGameRuleAsArray($ev->getGameRule());
					$server->broadcastPackets($server->getOnlinePlayers(), [$pk]);
				}
			}
		}
	}

	public function gameRuleExists(string $gameRule): bool
	{
		return isset($this->cachedGameRules[mb_strtolower($gameRule)]);
	}

	/**
	 * Gets a gamerule from the cache, returns null if not in cache.
	 *
	 * @param string $gameRule
	 * @return BoolGameRule|null
	 */
	public function getGameRule(string $gameRule): ?BoolGameRule
	{
		return $this->gameRuleExists($gameRule) ? $this->cachedGameRules[mb_strtolower($gameRule)] : null;
	}

	/**
	 * Gets the gamerule and returns it as an array for sending.
	 *
	 * @param string $gameRule
	 * @return string[]|GameRule[]|null
	 */
	public function getGameRuleAsArray(string $gameRule): ?array
	{
		return $this->gameRuleExists($gameRule) ? [mb_strtolower($gameRule) => $this->getGameRule($gameRule)] : null;
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
		return $this->gameRuleExists($gameRule) && $this->getGameRule($gameRule) !== null && $this->getGameRule($gameRule)->getValue() === true;
	}
}
