<?php
namespace hungergames;
use hungergames\api\scripts\HGAPIScriptManager;
use hungergames\command\HGCommand;
use hungergames\hgmap\MapBackup;
use hungergames\lib\GameStorage;
use hungergames\lib\mgr\GlobalManager;
use hungergames\lib\mgr\SignManager;
use hungergames\tasks\GameSaveTask;
use hungergames\tasks\LoadGamesTask;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use hungergames\obj\HungerGames;
use hungergames\lib\utils\Msg;
use hungergames\tasks\RefreshSignsTask;
class Loader extends PluginBase{
        /** @var Loader */
        private static $instance = null;

        /** @var GameStorage */
        private $storage;
        /** @var GlobalManager */
        private $globalManager;
        /** @var Config */
        private $messages;
        /** @var HGAPIScriptManager */
        private $scriptManager;
        /** @var MapBackup */
        private $mapBackup;
        /** @var SignManager */
        private $signManager;

        public function onLoad(){
                while(!self::$instance instanceof $this){
                        self::$instance = $this;
                }
        }

        public function onEnable(){
                $this->storage = new GameStorage();
                $this->globalManager = new GlobalManager($this);
                $this->scriptManager = new HGAPIScriptManager($this);
                $this->mapBackup = new MapBackup($this);
                $this->signManager = new SignManager($this);
                $this->getScheduler()->scheduleDelayedTask(new LoadGamesTask($this), 20);
                $h = $this->getScheduler()->scheduleDelayedRepeatingTask($t = new RefreshSignsTask($this), 20 * 5, 20);
                $t->setHandler($h);
                $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
                @mkdir($this->dataPath());
                @mkdir($this->dataPath() . "arenas/");
                @mkdir($this->dataPath() . "resources/");
                @mkdir($this->dataPath() . "scripts/");
                @mkdir($this->dataPath() . "scriptConfigs/");
                @mkdir($this->dataPath() . "mapBackups/");
                $this->messages = new Config($this->dataPath() . "messages.yml", Config::YAML, Msg::getDefaultHGMessages());
                $this->scriptManager->loadScripts();
        }

        /**
         * HungerGames base class
         *
         * @return Loader
         */
        public static function getInstance(){
                return self::$instance;
        }

        /**
         * HungerGames base folder
         *
         * @return string
         */
        public function dataPath(){
                return $this->getDataFolder();
        }

        /**
         * HungerGames arena folder
         *
         * @return string
         */
        public function getArenasDataPath(){
                return $this->dataPath() . "arenas/";
        }

        /**
         * HungerGames resource data path
         *
         * @return string
         */
        public function getResourceDataPath(){
                return $this->dataPath() . "resources/";
        }

        /**
         * Creates HungerGames resource name
         *
         * @param             $resource
         * @param HungerGames $data
         */
        public function createGameResource($resource, HungerGames $data){
                file_put_contents($this->getResourceDataPath() . $resource . ".dat", serialize($data));
        }

        /**
         * Deletes HungerGames resource name
         *
         * @param $resource
         */
        public function deleteGameResource($resource){
                if($this->gameResourceExists($resource)){
                        unlink($this->getResourceDataPath() . $resource . ".dat");
                }
        }

        /**
         * Gets game resource by Id
         *
         * @param $resource
         * @return HungerGames|null
         */
        public function getGameResource($resource){
                if(file_exists($this->getResourceDataPath() . $resource . ".dat")){
                        return unserialize(file_get_contents($this->getResourceDataPath() . $resource . ".dat"));
                }
                return null;
        }

        /**
         * Checks if game resource exists
         *
         * @param $resource
         * @return bool
         */
        public function gameResourceExists($resource){
                return file_exists($this->getResourceDataPath() . $resource . ".dat");
        }

        /**
         * Updates resource by recreating it
         *
         * @param             $resource
         * @param HungerGames $data
         */
        public function updateResourceData($resource, HungerGames $data){
                $this->createGameResource($resource, $data);
        }

        /**
         * Gets all games
         *
         * @return HungerGames[]|null
         */
        public function getAllGameResources(){
                $data = [];
                $res = glob($this->getResourceDataPath() . "*", GLOB_BRACE);
                foreach($res as $ret){
                        if(substr($ret, strlen($ret) - 4) === ".dat"){
                                $data[] = unserialize(file_get_contents($ret));
                        }
                }
                return $data === null ? null : $data;
        }

        /**
         * Delete HungerGames game arena
         *
         * @param HungerGames $game
         */
        public function deleteGameArena(HungerGames $game){
                unlink($this->getArenasDataPath() . $game->getName() . ".yml");
        }

        /**
         * Gets all games
         *
         * @return Config[]
         */
        public function getAllGameArenas(){
                $data = [];
                $res = glob($this->getArenasDataPath() . "*.yml", GLOB_BRACE);
                foreach($res as $ret){
                        $data[] = new Config($ret, Config::YAML);
                }
                return $data === null ? null : $data;
        }

        /**
         * Checks if game arena exists
         *
         * @param $name
         * @return bool
         */
        public function gameArenaExists($name){
                return file_exists($this->getArenasDataPath() . $name . ".yml");
        }

        /**
         * Gets game arena by name
         *
         * @param $name
         * @return null|Config
         */
        public function getGameArenaByName($name){
                return file_exists($this->getArenasDataPath() . $name . ".yml") ? new Config($this->getArenasDataPath() . $name . ".yml", Config::YAML) : null;
        }

        /**
         * Game storage, game info is stored
         *
         * @return GameStorage
         */
        public function getStorage(): GameStorage{
                return $this->storage;
        }

        /**
         * Get global manager, manage all games
         *
         * @return GlobalManager
         */
        public function getGlobalManager(): GlobalManager{
                return $this->globalManager;
        }

        /**
         * Get script manager, manage all scripts
         *
         * @return HGAPIScriptManager
         */
        public function getScriptManager(): HGAPIScriptManager{
                return $this->scriptManager;
        }

        /**
         * Gets map backup to backup and reset maps
         *
         * @return MapBackup
         */
        public function getMapBackup(): MapBackup{
                return $this->mapBackup;
        }

        /**
         * Gets sign manager, to refresh all signs
         *
         * @return SignManager
         */
        public function getSignManager(): SignManager{
                return $this->signManager;
        }

        /**
         * @param string $key
         * @param string $message
         */
        public function pushMessage(string $key, string $message){
                if(!$this->messages->exists($key)){
                        $this->messages->set($key, $message);
                        $this->messages->save();
                }
        }

        /**
         * Get all the messages
         *
         * @return string[]
         */
        public function getMessages(){
                return $this->messages->getAll();
        }

        /**
         * Gets message by index
         *
         * @param $message
         * @return string
         */
        public function getMessage($message){
                return $this->getMessages()[$message];
        }

        /**
         * Create HungerGames game arena
         *
         * @param HungerGames $game
         */
        public function createGameArena(HungerGames $game){
                $contents =
                    [

                        "sign_line_1" => "&6-=&e[&fS&cG&e]&6=-",
                        "sign_line_2" => "&f{on}&f/&a{max}",
                        "sign_line_3" => "&aGame: &f{game}",
                        "sign_line_4" => "&eStatus: {status}",
                        "is_sky_wars" => "no",
                        "clear_inventory_on_join" => true,
                        "min_players" => (int)2,
                        "max_players" => (int)8,
                        "game_seconds" => (float)60 * 5,
                        "waiting_seconds" => (float)60,
                        "game_level" => "world",
                        "refill_chest_after_seconds" => (float)60 * 2.5,
                        "chest_items" =>
                            [
                                "272 0 1",
                                "298 0 1",
                                "299 0 1",
                                "300 0 1",
                                "301 0 1"
                            ],
                        "lobby_pos" =>
                            [
                                "x" => (float)127,
                                "y" => (float)4,
                                "z" => (float)128,
                                "level" => "world"
                            ],
                        "death_match_pos" =>
                            [
                                "x" => (float)140,
                                "y" => (float)4,
                                "z" => (float)150,
                                "level" => "world"
                            ],
                        "slots" => [
                            "1" =>
                                [
                                    "x" => (float)128,
                                    "y" => (float)4,
                                    "z" => (float)129,
                                ],
                            "2" =>
                                [
                                    "x" => (float)138,
                                    "y" => (float)4,
                                    "z" => (float)139,
                                ],
                        ],
                        "sign_list" => []
                    ];
                $c = new Config($this->getArenasDataPath() . $game->getName() . ".yml", Config::YAML, $contents);
                $c->save();
        }
}
