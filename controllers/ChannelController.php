<?php
namespace Zylon\Controller;

use Zylon\SimpleFunnel;

use function Zylon\Async\async;

class ChannelController
{
    public function get($request)
    {
        return async(function() {
            header("Content-Type: text/plain");

            $response = yield SimpleFunnel::funnelCurrentPage();
            var_dump($response);
        });
    }
}

return new ChannelController();