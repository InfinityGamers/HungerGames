<?php
namespace hungergames\tasks;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
class DeathMatchTask extends Task{
        /** @var Loader */
        private $HGApi;
        /** @var Loader */
        private $game;

        public function __construct(Loader $main, HungerGames $game){
                $this->HGApi = $main;
                $this->game = $game;
        }

        /**
         * @param $currentTick
         */
        public function onRun(int $currentTick){
                $count = $this->HGApi->getStorage()->getPlayersInGameCount($this->game);
                if($count < 2){
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->HGApi->getGlobalManager()->getGameManager($this->game)->setStatus("reset");
                        foreach($this->HGApi->getStorage()->getPlayersInGame($this->game) as $p){
                                $p->teleport($this->game->getLobbyPosition());
                                $p->getInventory()->clearAll();
                                $this->HGApi->getScriptManager()->callOnPlayerWinGame($p, $this->game);
                                $msg = Msg::getHGMessage("hg.message.win");
                                $msg = str_replace(["%game%", "%player%"], [$this->game->getName(), $p->getName()], $msg);
                                $this->HGApi->getServer()->broadcastMessage(Msg::color($msg));
                        }
                        $this->HGApi->getStorage()->removePlayersInGame($this->game);
                        $this->game->resetGameLevelBackup();
                        $this->HGApi->getLogger()->info(TextFormat::GREEN . "Resetting map for game '" . TextFormat::YELLOW . $this->game->getName() . TextFormat::GREEN . "'");
                        $this->HGApi->getGlobalManager()->getGameManager($this->game)->refresh();
                        return;
                }
        }
}
