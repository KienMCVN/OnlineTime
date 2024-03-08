<?php
namespace KienMC\OnlTime;

use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\{PlayerJoinEvent, PlayerQuitEvent};
use pocketmine\command\{Command, CommandSender, CommandExecutor};
use KienMC\OnlTime\FormAPI\{Form, FormAPI, SimpleForm, CustomForm, ModalForm};
use KienMC\OnlTime\TimeTask;

class Main extends PluginBase implements Listener{
	
	public $time;

	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->time=new Config($this->getDataFolder()."time.yml",Config::YAML);
		$this->getScheduler()->scheduleRepeatingTask(new TimeTask($this), 20);
	}
		
	public function onDisable(): void{
		$this->time->save();
		$this->getConfig()->save();
	}
	
	public function onJoin(PlayerJoinEvent $ev){
		$player=$ev->getPlayer();
		$name=$player->getName();
		if(!$this->time->exists($name)){
			$this->time->set($name, 0);
			$this->time->save();
		}
		$this->time->save();
	}
	
	public function onQuit(PlayerQuitEvent $ev){
		$this->time->save();
	}
	
	public function addTime(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$name=$player->getName();
			if(!$this->time->exists($name)){
				$this->time->set($name, 0);
				$this->time->save();
			}
			$data=$this->time->get($name);
			$newdata=(int)($data)+1;
			$this->time->set($name, (int)($newdata));
			$this->time->save();
		}
	}

	public function getTime($player): ?array{
		if(!$this->time->exists($player)) return null;
		$data=$this->time->get($player);
		if($data>=3600){
			$h=floor($data/3600);
			$data=$data%3600;
			if($h<10){
				$h="0".$h;
			}
		}else{
			$h="00";
		}
		if($data>=60){
			$m=floor($data/60);
			$data=$data%60;
			if($m<10){
				$m="0".$m;
			}
		}else{
			$m="00";
		}
		if($data>=10){
			$s=$data;
		}else{
			$s="0".$data;
		}
		$time=[(string)$h,(string)$m,(string)$s];
		return $time;
	}
	
	public function getHourTime($player): ?float{
		if(!$this->time->exists($player)) return null;
		$data=$this->time->get($player);
		$h=floor($data/3600);
		return $h;
	}
	
	public function getMinTime($player): ?float{
		if(!$this->time->exists($player)) return null;
		$data=$this->time->get($player);
		$m=floor($data/60);
		return $m;
	}
	
	 public function getSecTime($player): ?float{
		if(!$this->time->exists($player)) return null;
		$data=$this->time->get($player);
		return $data;
	}
	
	public function setTime($player, $data){
		if(!$this->time->exists($player)) return;
		$this->time->set($player, (int)($data));
		$this->time->save();
	}
	
	public function giveTime($player, $data){
		if(!$this->time->exists($player)) return;
		$data=(int)($this->time->get($player))+(int)($data);
		$this->time->set($player, (int)($data));
		$this->time->save();
	}
	
	public function takeTime($player, $data){
		if(!$this->time->exists($player)) return;
		$data=(int)($this->time->get($player))-(int)($data);
		$this->time->set($player, (int)($data));
		$this->time->save();
	}
	
	public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool{
		$name=$cmd->getName();
		if($name=="onlinetime"){
			if(!$player instanceof Player){
				$player->sendMessage("Use Command In Game");
				return true;
			}
			$this->topOnline($player, 1);
			return true;
		}
		return true;
	}
	
	public function topOnline($player, $page){
		$pperpage=$this->getConfig()->get("player.per.page");
		$all=$this->time->getAll();
		arsort($all);
		$totalpage=ceil(count($all)/$pperpage);
		$begin=(int)(($page-1)*$pperpage)+1;
		$end=min($begin+$pperpage-1, count($all));
		$msg="";
		$i=1;
		$top="Not Found";
		foreach($all as $name => $data){
			if($i>=$begin && $i<=$end){
				$time=$this->getTime($name);
				$h=$time[0];
				$m=$time[1];
				$s=$time[2];
				$msg.=" §l§c➥§b Top ".$i.": §e".$name." §c➵§a ".$h."§e h §a".$m."§e m §a".$s."§e s\n";
			}
			if($name==$player->getName()) $top=$i;
			++$i;
		}
		$form=new CustomForm(function(Player $player, $data) use($totalpage){
			if($data==null) return;
			if(!isset($data[1]) || !is_numeric($data[1]) || (int)($data[1])<1 || (int)($data[1])>$totalpage) $data[1]=1;
			$this->topOnline($player, (int)($data[1]));
		});
		$name=$player->getName();
		$time=$this->getTime($name);
		$h=$time[0];
		$m=$time[1];
		$s=$time[2];
		$form->setTitle("§l§c♦§6 Top Online Time §c♦");
		$form->addLabel("§l§c•§e Your Online Time:§a ".$h."§e h §a".$m."§e m §a".$s."§e s\n§l§c•§e Your Top:§a ".$top."\n\n§l§c•§e Top:\n$msg \n\n§l§c•§e Page: §a".$page."/".$totalpage);
		$form->addInput("\n§l§c•§e Enter Page You Want To Go:");
		$form->sendToPlayer($player);
	}
}
