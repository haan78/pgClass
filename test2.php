<?php

//echo (new DateTime("NOW"))->format("c");
/*echo count( array_keys([1,2,3]) );

print_r(array_keys([1,2,3]));

$time = new \DateTime('14:00');
echo $time->format('H:i');
*/




//echo replaceQ("Ali?Barış?Öztürk",[ "1", "2", "3"]);
//echo str_replace(["?","?"],[1,2],"Ali ? ? Veli");

$subject = "offset12";
$num = 0;
if (preg_match('/^offset(\d+)$/', $subject, $matches)) {
    $num = $matches[1];
} elseif ($subject=="next") {
    $num +=1;
} elseif ($subject=="previous") {
    if ($num > 1) {
        $num += -1;
    }
} elseif ($subject == "first") {
    $num = 1;
}
echo $num;