<?php 
require_once("./lonet2.php");

$uname = "itf16a.mora@tbs1.nw.lo-net2.de";
$crypt = "0c0317a546490aea9b69c0c55c940a4f6b6a67fcbb19a02a0f96a3f16bc70ba0";
$pass = "password";

$lonet2 = new Lonet2($uname, $pass, $crypt);
if($lonet2->isLoggedin()){
	print_r(json_decode($lonet2->getJSON()));
}
 