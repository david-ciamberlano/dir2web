<?php

    define ('D2W_MAIN_PATH', "_dir2web");
    define ('D2W_SYSTEM_PATH', D2W_MAIN_PATH.'/_system');
    define ('D2W_THEME_PATH', D2W_SYSTEM_PATH."/themes");
    define ('D2W_SOURCE_PATH', D2W_SYSTEM_PATH."/src");
    define ('D2W_DATA_PATH', D2W_SYSTEM_PATH.'/data');
    define ('D2W_DB_PATH', D2W_DATA_PATH."/d2w.db");
    define ('D2W_CACHE_PATH', D2W_DATA_PATH.'/cache');
    define ('D2W_DEFAULT_PATH', D2W_SYSTEM_PATH."/default");

    require_once (D2W_SOURCE_PATH."/dispatcher.php");

    //$ms = memory_get_usage(true)/1048576;
    $disp = new Dispatcher();
    $disp->start();
    //$me = memory_get_usage(true)/1048576;
    
    //echo "memory:".$ms."--".$me;
?>