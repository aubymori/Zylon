<?php
namespace Zylon;

use YukisCoffee\CoffeeRequest\CoffeeRequest;
use YukisCoffee\CoffeeRequest\Promise;
use YukisCoffee\CoffeeRequest\Network\Request;
use YukisCoffee\CoffeeRequest\Network\Response;
use YukisCoffee\CoffeeRequest\Network\ResponseHeaders;

/**
 * A simple tool to funnel requests from a certain domain, while ignoring any
 * proxies active
 * 
 * @author Aubrey Pankow <aubyomori@gmail.com>
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 */
class SimpleFunnel
{
    /**
     * Hostname for funnelCurrentPage.
     * 
     * @var string
     */
    private static $hostname = "www.youtube.com";

    /**
     * Remove these request headers.
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
     * Remove these response headers.
     * LOWERCASE ONLY
     * 
     * @internal
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
     * @return Promise<SimpleFunnelResponse>
     */
    public static function funnel(array $opts): Promise/*<SimpleFunnelResponse>*/
    {
        // Required fields
        if (!isset($opts["host"]))
            self::error("No hostname specified");

        if (!isset($opts["uri"]))
            self::error("No URI specified");

        // Default options
        $opts += [
            "method" => "GET",
            "useragent" => "SimpleFunnel/1.0",
            "body" => "",
            "headers" => []
        ];

        $headers = [];

        foreach ($opts["headers"] as $key => $val) {
            if (!in_array(strtolower($key), self::$illegalRequestHeaders)) {
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

        $wrappedResponse = new Promise/*<Response>*/;

        $request = CoffeeRequest::request($url, $params);

        $request->then(function($response) use ($wrappedResponse) {
            foreach ($response->headers as $name => $value)
            {
                if (in_array(strtolower($name), self::$illegalResponseHeaders))
                {
                    unset($result[$name]);
                }
            }
            $wrappedResponse->resolve($response);
        });

        CoffeeRequest::run();

        return $wrappedResponse;
    }

    /**
     * Output a funnel error onto the page.
     * 
     * @param string $message
     */
    public static function error(string $message): void
    {
        http_response_code(500);
        echo("
        <title>SimpleFunnel Error</title>
        <style>body>*{margin:8px 0}</style>
        <h2>An error has occured in SimpleFunnel</h2>
        <p><b>Error</b>: $message</p>
        <small><i>Please report this to the GitHub.</i></small>
        ");
        return;
    }
    
    /**
     * Funnel a page with the current data.
     * 
     * @return Promise<SimpleFunnelResponse>
     */
    public static function funnelCurrentPage(): Promise/*<SimpleFunnelResponse>*/
    {
        return self::funnel([
            "method" => $_SERVER["REQUEST_METHOD"],
            "host" => self::$hostname,
            "uri" => $_SERVER["REQUEST_URI"],
            "useragent" => $_SERVER["HTTP_USER_AGENT"],
            "body" => file_get_contents("php://input"),
            "headers" => getallheaders()
        ]);
    }
}