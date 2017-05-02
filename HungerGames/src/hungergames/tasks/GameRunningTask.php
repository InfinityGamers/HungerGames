<?php
namespace hungergames\tasks;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\scheduler\PluginTask;
use pocketmine\tile\Chest;
use pocketmine\entity\Creature;

class GameRunningTask extends PluginTask{
    /** @var Loader */
    private $HGApi;
    /** @var HungerGames */
    private $game;
    /** @var float */
    private $seconds;
    public function __construct(Loader $main, HungerGames $game){
        parent::__construct($main);
        $this->HGApi = $main;
        $this->game = $game;
        $this->seconds = $game->getGameSeconds();
    }
    /**
     * @param $currentTick
     */
    public function onRun($currentTick){
        $count = $this->HGApi->getStorage()->getPlayersInGameCount($this->game);
        --$this->seconds;
        if($this->game->getGameSeconds() - $this->seconds <= $this->game->refillAfter()){
            foreach($this->game->gameLevel->getTiles() as $tile){
                if($tile instanceof Chest){
                    $tile->getInventory()->setContents($this->game->getChestItems());
                }
            }
            $msg = Msg::getHGMessage("hg.message.refill");
            $msg = str_replace("%game%", $this->game->getName(), $msg);
            $this->HGApi->getGlobalManager()->getGameManagerByName($this->game->getName())->sendGameMessage($msg);
        }
        if($count == 0){
            $this->HGApi->getServer()->getScheduler()->cancelTask($this->getTaskId());
            $this->HGApi->getGlobalManager()->getGameManager($this->game)->setStatus("open");
            $this->HGApi->getGlobalManager()->getGameManager($this->game)->refresh();
            return;
        }
        if($count == 1){
            $this->HGApi->getServer()->getScheduler()->cancelTask($this->getTaskId());
            $this->HGApi->getGlobalManager()->getGameManager($this->game)->setStatus("open");
            foreach($this->HGApi->getStorage()->getPlayersInGame($this->game) as $p){
                $p->teleport($this->game->getLobbyPosition());
                $p->getInventory()->clearAll();
                foreach ($this->HGApi->getScriptManager()->getScripts() as $script) {
                    if (!$script->isEnabled()) continue;
                    $script->onPlayerWinGame($p, $this->game);
                }
                $msg = Msg::getHGMessage("hg.message.win");
                $msg = str_replace(["%game%", "%player%"], [$this->game->getName(), $p->getName()], $msg);
                $this->HGApi->getServer()->broadcastMessage(Msg::color($msg));
            }
            foreach($this->HGApi->getServer()->getLevels() as $level) {
                foreach($level->getEntities() as $entity) {
                    if (!($entity instanceof Creature)){
                        $entity->close();
                    }
                }
            }
            $this->HGApi->getStorage()->removePlayersInGame($this->game);
            $lvl_path = Loader::getInstance()->getServer()->getDataPath()."worlds/";
            $this->HGApi->getMapBackup()->reset(Loader::getInstance()->dataPath()."mapBackups/".$this->game->gameLevel->getFolderName(), $lvl_path.$this->game->gameLevel->getFolderName());
            $this->HGApi->getGlobalManager()->getGameManager($this->game)->refresh();
            return;
        }
        if($count >= 2 and $this->seconds <= 0){
            $this->HGApi->getServer()->getScheduler()->cancelTask($this->getTaskId());
            $this->HGApi->getGlobalManager()->getGameManager($this->game)->setStatus("final");
            foreach($this->HGApi->getStorage()->getPlayersInGame($this->game) as $p){
                $p->teleport($this->game->getDeathMatchPosition());
            }
            $msg = Msg::getHGMessage("hg.message.deathMatch");
            $msg = str_replace("%game%", $this->game->getName(), $msg);
            $this->HGApi->getGlobalManager()->getGameManagerByName($this->game->getName())->sendGameMessage(Msg::color($msg));
            foreach ($this->HGApi->getScriptManager()->getScripts() as $script) {
                if (!$script->isEnabled()) continue;
                $script->onDeathMatchStart($this->HGApi->getStorage()->getPlayersInGame($this->game), $this->game);
            }
            $task = new DeathMatchTask($this->HGApi, $this->game);
            $h = $this->HGApi->getServer()->getScheduler()->scheduleRepeatingTask($task, 20);
            $task->setHandler($h);
            return;
        }
        $msg = Msg::getHGMessage("hg.message.dmTime");
        $msg = str_replace(["%game%", "%seconds%"], [$this->game->getName(), $this->seconds], $msg);
        $msg = Msg::color($msg);
        $this->HGApi->getGlobalManager()->getGameManager($this->game)->sendGamePopup($msg);
    }
}
