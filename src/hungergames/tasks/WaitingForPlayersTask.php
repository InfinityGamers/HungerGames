<?php
namespace hungergames\tasks;
use hungergames\lib\mgr\GameManager;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\scheduler\Task;
class WaitingForPlayersTask extends Task{
        /** @var Loader */
        private $HGApi;
        /** @var HungerGames */
        private $game;
        /** @var GameManager */
        private $manager;
        
        /**
         *
         * WaitingForPlayersTask constructor.
         * @param Loader      $main
         * @param HungerGames $game
         * 
         */
        public function __construct(Loader $main, HungerGames $game){
                $this->HGApi = $main;
                $this->game = $game;
                $this->manager = $main->getGlobalManager()->getGameManagerByName($game->getName());
        }

        /**
         * 
         * @param $tick
         * 
         */
        public function onRun(int $tick){
                $count = $this->HGApi->getStorage()->getAllWaitingPlayersInGameCount($this->game);
                if($count == 0){
                        $this->manager->setStatus("open");
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->manager->refresh();
                        return;
                }
                if($count < $this->game->getMinimumPlayers()){
                        $this->HGApi->getScriptManager()->callWhileWaitingForPlayers($this->HGApi->getStorage()->getPlayersInWaitingGame($this->game), $this->game);
                        $msg = Msg::getHGMessage("hg.message.awaiting");
                        $msg = str_replace("%game%", $this->game->getName(), $msg);
                        $this->HGApi->getGlobalManager()->getGameManagerByName($this->game->getName())->sendGamePopup(Msg::color($msg));
                        return;
                }
                if($count >= $this->game->getMinimumPlayers()){
                        $this->manager->setStatus("waiting");
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $task = new WaitingToStartTask($this->HGApi, $this->game);
                        $h = $this->HGApi->getScheduler()->scheduleRepeatingTask($task, 20);
                        $task->setHandler($h);
                        return;
                }
        }
}
