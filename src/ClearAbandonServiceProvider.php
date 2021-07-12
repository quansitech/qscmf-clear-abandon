<?php

namespace ClearAbandon;

use Illuminate\Support\ServiceProvider;
use ClearAbandon\Commands\ClearAbandonCommand;

class ClearAbandonServiceProvider extends ServiceProvider{
    protected $commands = [
        ClearAbandonCommand::class
    ];

    public function boot(){
        $configPath =  __DIR__.'/../clearAbandon.php';
        $this->publishes([
            $configPath => config_path('clearAbandon.php'),
        ]);
    }

    public function register(){
        $this->commands($this->commands);
    }
}