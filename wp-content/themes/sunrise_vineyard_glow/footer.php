<?php
    $footerHtml = <<<TXT
<!-- <a href="#">Contact Us</a> | <a href="#">Terms of Use</a> | <a href="#">Trademarks</a> 
| <a href="#">Privacy Statement</a> | -->
TXT;
?><div id=footernav style='text-align:left;'>
<?php
	/* A sidebar in the footer? Yep. You can can customize
	 * your footer with four columns of widgets.
	 */
	get_sidebar( 'footer' );
?>
</div><div class="cleared"></div>
<div class="Footer">
    <div class="Footer-inner">
                <a href="<?php bloginfo('rss2_url'); ?>" class="rss-tag-icon" title="RSS"></a>
                <div class="Footer-text">
<p>
<div id=hidden style='display:none;'><?php 
//global $encDn;
//if($encDn != ''){ 
    //$encDomain = strrev(gzinflate(base64_decode($encDn))); 
    //$alloweddomain = $encDomain;
    //$currServerName = str_replace("www.","",$_SERVER['SERVER_NAME']);
    //if($currServerName == $alloweddomain){
        //remove_action('wp_footer','lcmp_theme_options_end'); 
    //} 
//}    
 ?></div>

<?php 
global $themeFooter;
if($themeFooter==''){
echo $footerHtml; 
}else{
echo html_entity_decode($themeFooter);
}
?>
    Copyright &copy; <?php echo date('Y');?> <?php bloginfo('name'); ?>. 
<?php wp_loginout();?></p>
</div>
    </div>
    <div class="Footer-background">
    </div>
</div>

</div>
</div>
<div><?php wp_footer(); ?></div>
</div>

</body>
</html>
