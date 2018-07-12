<?php
namespace hungergames\tasks;
use hungergames\lib\mgr\GameManager;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

class GameRunningTask extends Task{
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
         * GameRunningTask constructor.
         *
         * @param Loader      $main
         * @param HungerGames $game
         *
         */
        public function __construct(Loader $main, HungerGames $game){
                $this->HGApi = $main;
                $this->game = $game;
                $this->seconds = $game->getGameSeconds();
                $this->manager = $main->getGlobalManager()->getGameManagerByName($game->getName());
        }

        /**
         *
         * @param $currentTick
         *
         */
        public function onRun(int $currentTick){
                $count = $this->HGApi->getStorage()->getPlayersInGameCount($this->game);
                --$this->seconds;
                if(fmod($this->game->refillAfter(), $this->seconds) === 0){
                        $this->manager->refillChests();
                        $msg = Msg::getHGMessage("hg.message.refill");
                        $msg = str_replace("%game%", $this->game->getName(), $msg);
                        $this->manager->sendGameMessage($msg);
                }
                if($count == 0){
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->manager->setStatus("reset");
                        $this->manager->refresh();
                        $this->game->resetGameLevelBackup();
                        $this->HGApi->getLogger()->info(TextFormat::GREEN . "Resetting map for game '" . TextFormat::YELLOW . $this->game->getName() . TextFormat::GREEN . "'");
                        return;
                }
                if($count == 1){
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->manager->setStatus("reset");
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
                        return;
                }
                if($count >= 2 and $this->seconds <= 0){
                        $this->HGApi->getScheduler()->cancelTask($this->getTaskId());
                        $this->manager->setStatus("reset");
                        $this->manager->refresh();

                        if($this->game->isSkyWars() !== "no"){
                                $this->HGApi->getScriptManager()->callOnGameEnd($this->HGApi->getStorage()->getPlayersInGame($this->game), $this->game);
                                foreach($this->HGApi->getStorage()->getPlayersInGame($this->game) as $p){
                                        $p->getInventory()->clearAll();
                                        $p->teleport($this->game->getLobbyPosition());
                                }
                                $this->HGApi->getStorage()->removePlayersInGame($this->game);
                                $msg = Msg::getHGMessage("hg.message.noWin");
                                $msg = str_replace("%game%", $this->game->getName(), $msg);
                                $this->HGApi->getServer()->broadcastMessage(Msg::color($msg));
                                $this->game->resetGameLevelBackup();
                                $this->HGApi->getLogger()->info(TextFormat::GREEN . "Resetting map for game '" . TextFormat::YELLOW . $this->game->getName() . TextFormat::GREEN . "'");
                                return;
                        }

                        foreach($this->HGApi->getStorage()->getPlayersInGame($this->game) as $p){
                                $p->teleport($this->game->getDeathMatchPosition());
                        }
                        $msg = Msg::getHGMessage("hg.message.deathMatch");
                        $msg = str_replace("%game%", $this->game->getName(), $msg);
                        $this->HGApi->getGlobalManager()->getGameManagerByName($this->game->getName())->sendGameMessage(Msg::color($msg));
                        $this->HGApi->getScriptManager()->callOnDeathMatchStart($this->HGApi->getStorage()->getPlayersInGame($this->game), $this->game);
                        $task = new DeathMatchTask($this->HGApi, $this->game);
                        $h = $this->HGApi->getScheduler()->scheduleRepeatingTask($task, 20);
                        $task->setHandler($h);
                        return;
                }

                if($this->game->isSkyWars() === "no"){
                        $msg = Msg::getHGMessage("hg.message.dmTime");
                        $msg = str_replace(["%game%", "%seconds%"], [$this->game->getName(), $this->seconds], $msg);
                        $msg = Msg::color($msg);
                        $this->manager->sendGamePopup($msg);
                }else{
                        $msg = Msg::getHGMessage("hg.message.swTimeLeft");
                        $msg = str_replace(["%game%", "%seconds%"], [$this->game->getName(), $this->seconds], $msg);
                        $msg = Msg::color($msg);
                        $this->manager->sendGamePopup($msg);
                }
        }
}
