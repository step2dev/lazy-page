<?php

arch('source does not depend on application namespaces')
    ->expect('Step2dev\\LazyPage')
    ->not->toUse('App\\');
