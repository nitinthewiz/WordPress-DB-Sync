<?php

include 'markdownify_extra.php';

define('DB_NAME', 'nitinwordpress');

define('DB_USER', 'root');

define('DB_PASSWORD', '');

define('DB_HOST', 'localhost');

$dbh = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);

mysql_select_db(DB_NAME,$dbh);

destroy("drafts/");

$qry1 = "select post_title, post_name, post_date, post_content, post_excerpt, ID, guid from wp_posts where post_status = 'publish' and post_type = 'post'";

$res = mysql_query($qry1,$dbh);

while($post = mysql_fetch_array($res) ){

    $title = $post["post_title"];

    $slug = $post["post_name"];

    $date = strtotime($post["post_date"]);

    $content = $post["post_content"];

    $target = "drafts/".date("Y",$date)."/";

    $name = $slug.".txt";// % [date.year, date.month, date.day,slug]

    $link = get_meta($post['ID'],"link-url");

    $image = get_meta($post['ID'],"image");

    $tagq = "select tm.term_id,tm.name from wp_term_relationships tr 

              inner join wp_term_taxonomy tt on tr.term_taxonomy_id = tt.term_taxonomy_id 

              inner join wp_terms tm on tm.term_id=tt.term_id  

              where tr.object_id={$post['ID']} and tt.taxonomy = 'post_tag'";

    $tags = array();

    $t = mysql_query($tagq,$dbh);

    while($tr = mysql_fetch_assoc($t) ){

        $tags[] = $tr['name'];

    }

    $leap = MDFY_LINKS_EACH_PARAGRAPH;

    $keephtml = MDFY_KEEPHTML;

    $md = new Markdownify_Extra($leap, MDFY_BODYWIDTH, $keephtml);

    $output = $post['post_title']. PHP_EOL;

    $output .= "===================". PHP_EOL;

    if( !empty($link) ){

        $output .= "Link: ".$link. PHP_EOL;

    }

    $output .= "Tags: ".implode(",",$tags). PHP_EOL;

    $output .= "Published: ".$post["post_date"]. PHP_EOL;

    $output .= "publish-now". PHP_EOL. PHP_EOL;

    if( !empty($image) ){

        $output .= '!['.$name.']('.$image.' "'.$name.'")'. PHP_EOL. PHP_EOL;

    }

    $dd = $md->parseString( $post['post_content'] );

    $dd = str_replace("’","'",$dd);

    $dd = str_replace('“','"',$dd);

    $dd = str_replace('“','"',$dd);

    $dd = mb_convert_encoding($dd, 'UTF-8', 'ISO-8859-1');

    $output .= $dd;

    if (!is_dir($target)) {
        // dir doesn't exist, make it
        mkdir($target);
    }

    file_put_contents("{$target}/{$name}",$output);

}

mysql_close($dbh);

function get_meta($pid,$key){

    global $dbh;

    $retVal = "";

    $sql = "SELECT meta_value FROM wp_postmeta WHERE post_id='{$pid}' AND meta_key='{$key}'";

    $mres = mysql_query($sql,$dbh) or die( mysql_error()."<br />".$sql );

    while( $mrow = mysql_fetch_assoc($mres) ){

        $retVal = $mrow['meta_value'];

    }

    return $retVal;

}

function destroy($dir) {

    $mydir = opendir($dir);

    while(false !== ($file = readdir($mydir))) {

        if($file != "." && $file != "..") {

            chmod($dir.$file, 0777);

            if(is_dir($dir.$file)) {

                chdir('.');

                destroy($dir.$file.'/');

                rmdir($dir.$file) or DIE("couldn't delete $dir$file<br />");

            }else

                unlink($dir.$file) or DIE("couldn't delete $dir$file<br />");

        }

    }

    closedir($mydir);

}

?>