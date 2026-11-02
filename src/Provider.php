<?php

namespace Ernestdefoe\Festoon;

use Ernestdefoe\Festoon\Look\Schedule;
use Flarum\Foundation\AbstractServiceProvider;

class Provider extends AbstractServiceProvider
{
    public function register(): void
    {
        // One of each per request: the looks are read and cleaned once, and the
        // date code remembers what it has already worked out.
        $this->container->singleton(Config::class);
        $this->container->singleton(Schedule::class);
    }
}
