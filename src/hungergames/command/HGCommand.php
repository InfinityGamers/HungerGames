<?php
namespace hungergames\command;
use hungergames\lib\utils\exc;
use hungergames\lib\utils\Info;
use hungergames\lib\utils\Msg;
use hungergames\Loader;
use hungergames\obj\HungerGames;
use hungergames\tasks\WaitingForPlayersTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class HGCommand extends Command implements PluginIdentifiableCommand{
        /** @var Loader */
        private $HGApi;

        public function __construct(Loader $main){
                $this->HGApi = $main;
        }

        /**
         * @param CommandSender $sender
         * @param string        $commandLabel
         * @param string[]      $args
         *
         * @return bool
         */
        public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
                if(!$sender instanceof Player){
                        $sender->sendMessage(Msg::color("&aPlease run this command in-game."));
                        return false;
                }
                if(empty($args[0])){
                        $sender->sendMessage(Msg::color("&a- /hg help"));
                        return false;
                }
                switch(strtolower($args[0])){
                        case "help":
                                $sender->sendMessage(Msg::color("&aHungerGames Command"));
                                $sender->sendMessage(Msg::color("&a- /hg join <game>"));
                                $sender->sendMessage(Msg::color("&a- /hg add <game>"));
                                $sender->sendMessage(Msg::color("&a- /hg del <game>"));
                                $sender->sendMessage(Msg::color("&a- /hg min <game> <number>"));
                                $sender->sendMessage(Msg::color("&a- /hg max <game> <number>"));
                                $sender->sendMessage(Msg::color("&a- /hg level <game> <level name>"));
                                $sender->sendMessage(Msg::color("&a- /hg ws <game> <seconds>"));
                                $sender->sendMessage(Msg::color("&a- /hg gs <game> <seconds>"));
                                $sender->sendMessage(Msg::color("&a- /hg lobby <game>"));
                                $sender->sendMessage(Msg::color("&a- /hg dm <game>"));
                                $sender->sendMessage(Msg::color("&a- /hg addslot <game> <name>"));
                                $sender->sendMessage(Msg::color("&a- /hg delslot <game> <name>"));
                                $sender->sendMessage(Msg::color("&a- /hg leave"));
                                return true;
                                break;
                        case "add":
                                if(!$sender->hasPermission("hg.command.add")) return false;
                                if(empty($args[1])){
                                        $sender->sendMessage(Msg::color("&a- /hg add <game>"));
                                        return true;
                                }
                                $game = $args[1];
                                if($this->HGApi->gameResourceExists($game) or $this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame already exists!"));
                                        return true;
                                }
                                $game1 = new HungerGames();
                                $sender->sendMessage(Msg::color("&aCreating game $game... Please wait..."));
                                $game1->loadGame($game1);
                                $game1->create($game);
                                $this->HGApi->getGlobalManager()->load($game1);
                                $sender->sendMessage(Msg::color("&aSuccessfully created game $game!"));
                                break;
                        case "del":
                                if(!$sender->hasPermission("hg.command.del")) return false;
                                if(empty($args[1])){
                                        $sender->sendMessage(Msg::color("&a- /hg del <game>"));
                                        return false;
                                }
                                $game = $args[1];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if(empty($args[2])){
                                        $sender->sendMessage(Msg::color("&cAre you sure you want to delete $game? &4&lYOU CAN NOT GET IT BACK!!"));
                                        $sender->sendMessage(Msg::color("&aIf you are sure please run: /hg del $game proceed"));
                                        return false;
                                }
                                if(strtolower($args[2]) !== "proceed"){
                                        $sender->sendMessage(Msg::color("&aDid you mean \"/hg del $game\"?"));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGameResource($game);
                                $this->HGApi->getGlobalManager()->remove($game1);
                                $game1->delete(true);
                                $sender->sendMessage(Msg::color("&cGame $game has been deleted! You can not get it back!"));
                                return true;
                        case "min":
                                if(!$sender->hasPermission("hg.command.min")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg min <game> <number>"));
                                        return false;
                                }
                                $game = $args[1];
                                $number = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if(!is_numeric($number)){
                                        $sender->sendMessage(Msg::color("&cInvalid int/number value."));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->setMinimumPlayers($number);
                                $sender->sendMessage(Msg::color("&cMinimum players of game $game have been set to $number."));
                                return true;
                        case "max":
                                if(!$sender->hasPermission("hg.command.max")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg max <game> <number>"));
                                        return false;
                                }
                                $game = $args[1];
                                $number = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if(!is_numeric($number)){
                                        $sender->sendMessage(Msg::color("&cInvalid int/number value."));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->setMaximumPlayers($number);
                                $sender->sendMessage(Msg::color("&aMaximum players of game $game have been set to $number."));
                                return true;
                        case "level":
                                if(!$sender->hasPermission("hg.command.level")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg level <game> <level name>"));
                                        return false;
                                }
                                $game = $args[1];
                                $level = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->setGameLevel($level);
                                $sender->sendMessage(Msg::color("&aSet game level of $game to $level."));
                                return true;
                        case "ws":
                                if(!$sender->hasPermission("hg.command.ws")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg ws <game> <seconds>"));
                                        return false;
                                }
                                $game = $args[1];
                                $seconds = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if(!is_numeric($seconds)){
                                        $sender->sendMessage(Msg::color("&cInvalid int/number value."));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->setWaitingSeconds($seconds);
                                $sender->sendMessage(Msg::color("&aSet waiting seconds of game $game to $seconds."));
                                return true;
                        case "gs":
                                if(!$sender->hasPermission("hg.command.ws")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg gs <game> <seconds>"));
                                        return false;
                                }
                                $game = $args[1];
                                $seconds = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if(!is_numeric($seconds)){
                                        $sender->sendMessage(Msg::color("&cInvalid int/number value."));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->setWaitingSeconds($seconds);
                                $sender->sendMessage(Msg::color("&aSet game seconds of game $game to $seconds."));
                                return true;
                        case "addslot":
                                if(!$sender->hasPermission("hg.command.slot.add")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg addslot <game> <name>"));
                                        return false;
                                }
                                $game = $args[1];
                                $slot = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                $game1->addSlot($sender, $slot);
                                $sender->sendMessage(Msg::color("&aAdded slot $slot for game $game."));
                                return true;
                        case "delslot":
                                if(!$sender->hasPermission("hg.command.slot.del")) return false;
                                if(empty($args[1]) or empty($args[2])){
                                        $sender->sendMessage(Msg::color("&a- /hg delslot <game> <name>"));
                                        return false;
                                }
                                $game = $args[1];
                                $slot = $args[2];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                $game1 = $this->HGApi->getGlobalManager()->getGameEditorByName($game);
                                if($game1->removeSlot($slot)){
                                        $sender->sendMessage(Msg::color("&aDeleted slot $slot for game $game."));
                                        return true;
                                }else{
                                        $sender->sendMessage(Msg::color("&cSlot $slot not found for game $game."));
                                        return false;
                                }
                        case "join":
                                if(empty($args[1])){
                                        $sender->sendMessage(Msg::color("&a- /hg delslot <game> <name>"));
                                        return false;
                                }
                                $game = $this->HGApi->getGlobalManager()->getGameByName($args[1]);
                                if($game === null){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                if($this->HGApi->getStorage()->isPlayerSet($sender) or $this->HGApi->getStorage()->isPlayerWaiting($sender)) return false;
                                if($this->HGApi->getGlobalManager()->getGameManager($game)->getStatus() === "running" || $this->HGApi->getGlobalManager()->getGameManager($game)->getStatus() === "reset") return false;
                                $this->HGApi->getGlobalManager()->getGameManager($game)->addWaitingPlayer($sender, true);
                                $sender->sendMessage(Msg::color("&aJoining game!"));
                                if($this->HGApi->getGlobalManager()->getGameManager($game)->isWaiting) return false;//checks if task started
                                $t = new WaitingForPlayersTask($this->HGApi, $game);
                                $h = $this->HGApi->getScheduler()->scheduleRepeatingTask($t, 20);
                                $t->setHandler($h);
                                $this->HGApi->getGlobalManager()->getGameManager($game)->isWaiting = true;
                                return true;
                        case "leave":
                                $p = $sender;
                                if($this->HGApi->getStorage()->isPlayerSet($p)){
                                        $game = $this->HGApi->getStorage()->getPlayerGame($p);
                                        if($game !== null){
                                                $this->HGApi->getGlobalManager()->getGameManager($game)->removePlayer($p, true);
                                                $p->sendMessage(Msg::color("&aExiting game..."));
                                                return true;
                                        }
                                }elseif($this->HGApi->getStorage()->isPlayerWaiting($p)){
                                        $game = $this->HGApi->getStorage()->getWaitingPlayerGame($p);
                                        if($game !== null){
                                                $this->HGApi->getGlobalManager()->getGameManager($game)->removeWaitingPlayer($p, true);
                                                $p->sendMessage(Msg::color("&aExiting game..."));
                                                return true;
                                        }
                                }else{
                                        $p->sendMessage(Msg::color("&cYou are not playing on any game."));
                                        return false;
                                }
                                return true;
                        case "lobby":
                                if(!$sender->hasPermission("hg.command.lobby")) return false;
                                if(empty($args[1])){
                                        $sender->sendMessage(Msg::color("&a- /hg lobby <game>"));
                                        return false;
                                }
                                $game = $args[1];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                $this->HGApi->getGlobalManager()->getGameEditorByName($game)->setLobbyPosition($sender);
                                $sender->sendMessage(Msg::color("&aSuccessfully set lobby position where you are standing!"));
                                return true;
                        case "dm":
                                if(!$sender->hasPermission("hg.command.dm")) return false;
                                if(empty($args[1])){
                                        $sender->sendMessage(Msg::color("&a- /hg dm <game>"));
                                        return false;
                                }
                                $game = $args[1];
                                if(!$this->HGApi->gameResourceExists($game) or !$this->HGApi->gameArenaExists($game)){
                                        $sender->sendMessage(Msg::color("&cGame does not exist!"));
                                        return false;
                                }
                                $this->HGApi->getGlobalManager()->getGameEditorByName($game)->setDeathMatchPosition($sender);
                                $sender->sendMessage(Msg::color("&aSuccessfully set death match position where you are standing!"));
                                return true;
                }
                return false;
        }

        /**
         * @return Loader
         */
        public function getPlugin(): Plugin{
                return $this->HGApi;
        }
}
