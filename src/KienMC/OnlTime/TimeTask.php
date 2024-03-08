<?php
namespace KienMC\OnlTime;

use pocketmine\scheduler\Task;
use KienMC\OnlTime\Main;

class TimeTask extends Task {

	private Main $plugin;

	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}

	public function onRun() : void {
		$this->plugin->addTime();
	}
}
