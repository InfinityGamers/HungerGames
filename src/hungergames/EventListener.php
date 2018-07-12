<?php
namespace hungergames;
use hungergames\lib\utils\Msg;
use hungergames\tasks\WaitingForPlayersTask;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\tile\Sign;
class EventListener implements Listener{
        /** @var Loader */
        private $HGApi;

        public function __construct(Loader $main){
                $this->HGApi = $main;
        }

        /**
         * @param PlayerMoveEvent $e
         */
        public function onMove(PlayerMoveEvent $e){
                $p = $e->getPlayer();
                $from = clone $e->getFrom();
                $to = $e->getTo();
                if($this->HGApi->getStorage()->isPlayerWaiting($p)){
                        if($to->x != $from->x or $to->y != $from->y or $to->z != $from->z){
                                $from->yaw = $to->yaw;
                                $from->pitch = $to->pitch;
                                $e->setTo($from);
                        }
                }
        }

        /**
         * @param SignChangeEvent $e
         *
         * @return bool|void
         *
         * @throws \InvalidStateException
         */
        public function onSignChange(SignChangeEvent $e){
                $p = $e->getPlayer();
                if(!$p->hasPermission("hg.sign.create")) return;
                $b = $e->getBlock()->level->getTile($e->getBlock());
                if($b instanceof Sign){
                        $line1 = $e->getLine(0);
                        $line2 = $e->getLine(1);
                        if($line1 === "hg" or $line1 === "[hg]"){
                                if(!$this->HGApi->getGlobalManager()->exists($line2)){
                                        $p->sendMessage(Msg::color("&cGame does not exist..."));
                                        return;
                                }
                                $game = $this->HGApi->getGlobalManager()->getGameEditorByName($line2);
                                $game->addSign($b);
                                $p->sendMessage(Msg::color("&aSuccessfully created HG sign!"));
                        }
                }
        }

        /**
         * @param PlayerInteractEvent $e
         */
        public function onInteract(PlayerInteractEvent $e){
                $p = $e->getPlayer();
                $b = $e->getBlock()->level->getTile($e->getBlock());
                if($b instanceof Sign){
                        if($this->HGApi->getSignManager()->isGameSign($b)){
                                $game = $this->HGApi->getSignManager()->getSignGame($b);
                                if($game === null) return;
                                if($this->HGApi->getStorage()->isPlayerSet($p) or $this->HGApi->getStorage()->isPlayerWaiting($p)) return;
                                if($this->HGApi->getGlobalManager()->getGameManager($game)->getStatus() === "running" || $this->HGApi->getGlobalManager()->getGameManager($game)->getStatus() === "reset") return;
                                $this->HGApi->getGlobalManager()->getGameManager($game)->addWaitingPlayer($p, true);
                                if($this->HGApi->getGlobalManager()->getGameManager($game)->isWaiting) return;//checks if task started
                                $t = new WaitingForPlayersTask($this->HGApi, $game);
                                $h = $this->HGApi->getScheduler()->scheduleRepeatingTask($t, 20);
                                $t->setHandler($h);
                                $this->HGApi->getGlobalManager()->getGameManager($game)->isWaiting = true;
                        }
                }
        }

        /**
         * @param EntityLevelChangeEvent $e
         */
        public function onLevelChange(EntityLevelChangeEvent $e){
                $p = $e->getEntity();
                if($p instanceof Player){
                        if($this->HGApi->getStorage()->isPlayerSet($p)){
                                $game = $this->HGApi->getStorage()->getPlayerGame($p);
                                if($game !== null) $this->HGApi->getGlobalManager()->getGameManager($game)->removePlayer($p, true);
                        }elseif($this->HGApi->getStorage()->isPlayerWaiting($p)){
                                $game = $this->HGApi->getStorage()->getWaitingPlayerGame($p);
                                if($game !== null) $this->HGApi->getGlobalManager()->getGameManager($game)->removeWaitingPlayer($p, true);
                        }
                }
        }

        /**
         * @param PlayerQuitEvent $e
         */
        public function playerQuitEvent(PlayerQuitEvent $e){
                $p = $e->getPlayer();
                if($this->HGApi->getStorage()->isPlayerSet($p)){
                        $game = $this->HGApi->getStorage()->getPlayerGame($p);
                        if($game !== null) $this->HGApi->getGlobalManager()->getGameManager($game)->removePlayer($p, true);
                }elseif($this->HGApi->getStorage()->isPlayerWaiting($p)){
                        $game = $this->HGApi->getStorage()->getWaitingPlayerGame($p);
                        if($game !== null) $this->HGApi->getGlobalManager()->getGameManager($game)->removeWaitingPlayer($p, true);
                }
        }

        /**
         * @param PlayerDeathEvent $e
         */
        public function playerDeathEvent(PlayerDeathEvent $e){
                $p = $e->getEntity();
                if($this->HGApi->getStorage()->isPlayerSet($p)){
                        $game = $this->HGApi->getStorage()->getPlayerGame($p);
                        if($game !== null){
                                $this->HGApi->getGlobalManager()->getGameManager($game)->removePlayer($p);
                        }
                        $count = $this->HGApi->getStorage()->getPlayersInGameCount($game);
                        if($count > 1){
                                $msg = Msg::getHGMessage("hg.message.death");
                                $msg = str_replace(["%player%", "%game%", "%left%"], [$p->getName(), $game->getName(), $count], $msg);
                                $this->HGApi->getGlobalManager()->getGameManager($game)->sendGameMessage(Msg::color($msg));
                        }
                }
        }

        /**
         * @param BlockBreakEvent $e
         */
        public function onBlockBreak(BlockBreakEvent $e){
                if($this->HGApi->getStorage()->isPlayerWaiting($e->getPlayer())){
                        $e->setCancelled();
                }
        }
}
