<?php
namespace Zylon;

use YukisCoffee\CoffeeRequest\CoffeeRequest;

use function Zylon\Async\async;

/**
 * A simple tool to funnel requests from a certain domain,
 * while ignoring any proxies active
 * 
 * @author Aubrey P. <aubyomori@gmail.com>
 * @author Taniko Y. <kirasicecreamm@gmail.com>
 */
class SimpleFunnel
{
    /**
     * Hostname for funnelCurrentPage
     * 
     * @var string
     */
    public static $hostname = "www.youtube.com";

    /**
     * Remove these request headers
     * LOWERCASE ONLY
     * 
     * @var string[]
     */
    public static $illegalRequestHeaders = [
        "accept",
        "accept-encoding",
        "host",
        "origin",
        "referer"
    ];

    /**
     * Remove these response headers
     * LOWERCASE ONLY
     * 
     * @var string[]
     */
    public static $illegalResponseHeaders = [
        "content-encoding",
        "content-length"
    ];

    /**
     * Funnel a response through.
     * 
     * @param array $opts  Options such as headers and request method
     * @return object
     */
    public static function funnel(array $opts): object
    {
        return async(function() use ($opts) {
            // Required fields
            if (!isset($opts["host"])) return (object) [
                "error" => "No hostname specified"
            ];
            if (!isset($opts["uri"])) return (object) [
                "error" => "No URI specified"
            ];

            // Default options
            $opts += [
                "method" => "GET",
                "useragent" => "SimpleFunnel/1.0",
                "body" => "",
                "headers" => []
            ];

            $illegalRequestHeaders = [
                "Accept",
                "Accept-Encoding",
                "Host",
                "Origin",
                "Referer"
            ];

            $headers = [];
            foreach ($opts["headers"] as $key => $val)
            {
                if (!in_array($key, $illegalRequestHeaders)) 
                {
                    $headers[$key] = $val;
                }
            }

            $headers["Host"] = $opts["host"];
            $headers["Origin"] = "https://" . $opts["host"];
            $headers["Referer"] = "https://" . $opts["host"] . $opts["uri"];

            // Set up cURL and perform the request
            $url = "https://" . $opts["host"] . $opts["uri"];

            // Set up the request.
            $params = [
                "method" => $opts["method"],
                "headers" => $headers,
                "redirect" => "manual",
            ];

            if ("POST" == $params["method"])
            {
                $params["body"] = $opts["body"];
            }

            CoffeeRequest::run();

            return (yield CoffeeRequest::request($url, $params));
        });
    }

    /**
     * Output a funnel response onto the page.
     * 
     * @param object $funnelData
     */
    public static function output(object $funnelData): void {
        if (isset($funnelData->error)) {
            http_response_code(500);
            echo("
            <title>SimpleFunnel Error</title>
            <style>body>*{margin:8px 0}</style>
            <h2>An error has occured in SimpleFunnel</h2>
            <p><b>Error</b>: " . $funnelData->error . "</p>
            <small><i>Please report this to the GitHub.</i></small>
            ");
            return;
        }

        $illegalResponseHeaders = [
            "content-encoding"
        ];

        http_response_code($funnelData->status);
        foreach($funnelData->headers as $name => $value)
        if (!in_array($name, $illegalResponseHeaders)) {
            header("$name: $value");
        }
        echo $funnelData->getText();
        die();
    }
    
    /**
     * Funnel a page with the current data.
     * 
     * @param  bool $output  Whether or not to output the page
     * @return ?object
     */
    public static function funnelCurrentPage(bool $output = false): ?object {
        $funnel = self::funnel([
            "method" => $_SERVER["REQUEST_METHOD"],
            "host" => self::$hostname,
            "uri" => $_SERVER["REQUEST_URI"],
            "useragent" => $_SERVER["HTTP_USER_AGENT"],
            "body" => file_get_contents("php://input"),
            "headers" => getallheaders()
        ]);

        if (!$output)
        {
            return $funnel;
        }

        self::output($funnel);
    }
}