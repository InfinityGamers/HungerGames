<?php
namespace hungergames\tasks;
use hungergames\lib\mgr\GameManager;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\block\Block;
use pocketmine\scheduler\Task;

class WaitingToStartTask extends Task{
        /** @var Loader */
        private $HGApi;
        /** @var HungerGames */
        private $game;
        /** @var float */
        private $seconds;
        /** @var GameManager */
        private $manager;

        /**
         *
         * WaitingToStartTask constructor.
         *
         * @param Loader      $main
         * @param HungerGames $game
         *
         */
        public function __construct(Loader $main, HungerGames $game){
                $this->HGApi = $main;
                $this->game = $game;
                $this->seconds = $game->getWaitingSeconds();
                $this->manager = $main->getGlobalManager()->getGameManagerByName($game->getName());
        }

        /**
         *
         * @param $tick
         *
         */
        public function onRun(int $tick){
                $count = $this->HGApi->getStorage()->getAllWaitingPlayersInGameCount($this->game);
                --$this->seconds;
                if($count == 0){
                        $this->manager->setStatus("open");
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->manager->refresh();
                        return;
                }
                if($this->seconds > 0){
                        if($count >= $this->game->getMinimumPlayers()){
                                $message = str_replace(["%seconds%", "%game%"], [$this->seconds, $this->game->getName()], Msg::getHGMessage("hg.message.waiting"));
                                $this->manager->sendGamePopup(Msg::color($message));
                                $this->HGApi->getScriptManager()->callWhileWaitingToStart($this->HGApi->getStorage()->getPlayersInWaitingGame($this->game), $this->game);
                                return;
                        }
                        if($count < $this->game->getMinimumPlayers()){
                                $this->manager->setStatus("waiting");
                                $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                                $task = new WaitingForPlayersTask($this->HGApi, $this->game);
                                $h = $this->HGAp->getScheduler()->scheduleRepeatingTask($task, 20);
                                $task->setHandler($h);
                                return;
                        }
                        return;
                }
                if($this->seconds == 0 and $count >= $this->game->getMinimumPlayers()){
                        $task = new GameRunningTask($this->HGApi, $this->game);
                        $h = $this->HGApi->getScheduler()->scheduleRepeatingTask($task, 20);
                        $task->setHandler($h);
                        foreach($this->HGApi->getStorage()->getPlayersInWaitingGame($this->game) as $p){
                                if($p->getLevel()->getBlock($p->subtract(0, 1))->getId() === Block::GLASS){
                                        $p->getLevel()->setBlock($p->subtract(0, 1), Block::get(0));
                                }
                        }
                        $this->manager->setStatus("running");
                        $this->manager->refillChests();
                        foreach($this->HGApi->getStorage()->getPlayersInWaitingGame($this->game) as $p){
                                $this->HGApi->getStorage()->addPlayer($p, $this->game);
                        }
                        $this->HGApi->getStorage()->removePlayersInWaitingGame($this->game);
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $message = str_replace("%game%", $this->game->getName(), Msg::getHGMessage("hg.message.start"));
                        $this->manager->sendGameMessage(Msg::color($message));
                        $this->HGApi->getScriptManager()->callOnGameStart($this->HGApi->getStorage()->getPlayersInGame($this->game), $this->game);
                        
                        return;
                }
        }
}
