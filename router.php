<?php
use Zylon\Controller;

Controller::route([
    "get" => [
        "default" => "ChannelController"
    ]
]);

Controller::run();