<?php
namespace hungergames\tasks;
use hungergames\Loader;
use pocketmine\scheduler\Task;
class RefreshSignsTask extends Task{
        /** @var Loader */
        private $HGApi;

        /**
         *
         * RefreshSignsTask constructor.
         *
         * @param Loader $main
         *
         */
        public function __construct(Loader $main){
                $this->HGApi = $main;
        }

        /**
         *
         * @param $currentTick
         *
         */
        public function onRun(int $currentTick){
                $this->HGApi->getSignManager()->refreshAllSigns();
        }
}
