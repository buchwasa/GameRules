<?php
declare(strict_types=1);

namespace buchwasa\libgamerules;

use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;

class GameRuleChangedEvent extends Event
{
	use CancellableTrait;

	/** @var string */
	private string $gameRule;
	/** @var bool */
	private bool $enabled;

	public function __construct(string $gameRule, bool $enabled)
	{
		$this->gameRule = $gameRule;
		$this->enabled = $enabled;
	}

	public function getGameRule(): string
	{
		return $this->gameRule;
	}

	public function isGameRuleEnabled(): bool
	{
		return $this->enabled;
	}
}
