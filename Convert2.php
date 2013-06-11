<?php

include '/markdownify/markdownify_extra.php';
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-includes/class-wp.php' );
require_once(ABSPATH . 'wp-includes/functions.php' );
require_once(ABSPATH . 'wp-includes/plugin.php' );

function Convert2(){

    //$base = plugin_dir_path(__FILE__);
    //echo $base;
    //echo ABSPATH;
    destroy(dirname(__FILE__) ."/drafts/");

    $args = array(
            'numberposts'   => -1,
            'post_status'   => array('draft', 'publish')
    );
    
    $posts_array = get_posts($args);

    foreach ($posts_array as $post) {
        $title = $post->post_title;

        $slug = $post->post_name;

        $date = strtotime($post->post_date);

        $content = $post->post_content;

        $target = dirname(__FILE__) ."/drafts/".date("Y",$date)."/";

        if( !empty($slug) ){
            $name = $slug.".txt";// % [date.year, date.month, date.day,slug]
        }
        else{
            $name = $title.".txt";   
        }

        $link = get_meta($post->ID,"link-url");

        $image = get_meta($post->ID,"image");

        $tagq = wp_get_post_tags( $post->ID);

        $tags = array();

        foreach ($tagq as $tag) {
            $tags[] = $tag->name;    
        }


        $leap = MDFY_LINKS_EACH_PARAGRAPH;

        $keephtml = MDFY_KEEPHTML;

        $md = new Markdownify_Extra($leap, MDFY_BODYWIDTH, $keephtml);

        $output = $post->post_title. PHP_EOL;

        $output .= "===================". PHP_EOL;

        if( !empty($link) ){

            $output .= "Link: ".$link. PHP_EOL;

        }

        $output .= "Tags: ".implode(",",$tags). PHP_EOL;

        $output .= "Published: ".$post->post_date. PHP_EOL;

        $output .= $post->post_status. PHP_EOL. PHP_EOL;

        if( !empty($image) ){

            $output .= '!['.$name.']('.$image.' "'.$name.'")'. PHP_EOL. PHP_EOL;

        }

        $dd = $md->parseString( $post->post_content );

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
}



function get_meta($pid,$key){

    $retval = get_post_meta($pid,$key);

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