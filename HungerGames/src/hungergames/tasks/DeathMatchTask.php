<?php
namespace hungergames\tasks;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\scheduler\PluginTask;
use pocketmine\entity\Creature;

class DeathMatchTask extends PluginTask{
    /** @var Loader */
    private $HGApi;
    /** @var Loader */
    private $game;
    public function __construct(Loader $main, HungerGames $game){
        parent::__construct($main);
        $this->HGApi = $main;
        $this->game = $game;
    }
    /**
     * @param $currentTick
     */
    public function onRun($currentTick){
        $count = $this->HGApi->getStorage()->getPlayersInGameCount($this->game);
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
    }
}
