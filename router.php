<?php
if (isset($_GET["enable_polymer"]) && $_GET["enable_polymer"] != "0")
{
    \Zylon\SimpleFunnel::funnelCurrentPage(true);
}