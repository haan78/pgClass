<!DOCTYPE html>
 <html>
   <head>
     <meta charset="utf-8"/>
     <meta name="viewport" content="width=device-width, initial-scale=1"/>
     <link rel="shortcut icon" href="data:image/x-icon;," type="image/x-icon"> 
   </head>
   <body>
     <?php
        require_once "test3.php";
        $list = page();
        $lc = count($list);
        $cp = pageNum();
        echo "<table>";
        for ($i=0; $i<$lc; $i++) {
            echo "<tr>";
            foreach( $list[$i] as $k => $v ) {
                echo "<td>";
                echo $v;
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
     ?>
     <button onclick="window.location.href='index.php'">First</button>
     <button onclick="page(-1)">Previous</button>
     <span><?php echo $cp; ?></span>
     <button onclick="page(1)" >Next</button>
     <script>
         var lc = <?php echo $lc; ?>;
         var cp = <?php echo $cp; ?>;

         function page(num) {
            var url = (window.location.href+"").split("?")[0];
            var npn = cp;
            if ( lc == 20 &&  num == 1 ) {
                npn += 1;
            } else if( cp > 1 && num == -1 ) {
                npn += -1;
            }

            url+="?p="+(npn);
            window.location.href = url;
         }
     </script>
   </body>
 </html>