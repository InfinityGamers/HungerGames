<?php
namespace hungergames\hgmap;
use hungergames\Loader;
use hungergames\tasks\AsyncMapBackup;
class MapBackup{

        /** @var Loader */
        public $loader;

        /**
         *
         * MapBackup constructor.
         *
         * @param Loader $loader
         *
         */
        public function __construct(Loader $loader){
                $this->loader = $loader;
        }

        /**
         *
         * Writes folder backup
         *
         * @param        $source
         * @param        $destination
         * @param string $game
         *
         * @return void
         */
        public function write($source, $destination, string $game){
                $this->asyncWrite($source, $destination, $game);
        }


        /**
         *
         * @param        $source
         * @param        $destination
         * @param string $game
         */
        public function asyncWrite($source, $destination, string $game){
                $this->getServer()->getAsyncPool()->submitTask(new AsyncMapBackup($source, $destination, $game));
        }

        /**
         *
         * Resets game map
         *
         * @param        $source
         * @param        $destination
         * @param string $game
         *
         * @return void
         *
         */
        public function reset($source, $destination, string $game){
                $this->asyncWrite($source, $destination, $game);
        }

}
