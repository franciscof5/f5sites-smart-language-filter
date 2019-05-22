<?php
/*
Plugin Name: F5 Sites | Smart Language Filter
Plugin URI: https://www.f5sites.com/f5sites-smart-language-filter/
Plugin Description: Use tags lang-en or lang-x to filter post content. It first try to use WooCommerce geolocalization, then basic php http geolocation.
Plugin Author: Francisco Mat
Author URI: https://www.franciscomat.com/
License: GPLv3
*/

function smartlang_set_user_language() {
	global $user_prefered_language;
	session_start();

	//Try WooCommerce geolocate
	if(class_exists("WC_Geolocation")) {
		$wclocation = WC_Geolocation::geolocate_ip();
		$user_location_georefered = $wclocation['country'];
	}

	//If not, then uses basic http
	if(!$user_location_georefered) {
		if(function_exists("locale_accept_from_http"))
			$user_location_georefered = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		//Last case it sets en_US
		else
			$user_location_georefered = "en_US";
	}

	//Corrects BR to pt_BR
	if($user_location_georefered=="BR")
		$user_location_georefered="pt_BR";

	//Finally set user prefered language
	$user_prefered_language = $user_location_georefered;
	
	//Check/Save in session
	//if(!isset($_SESSION["user_prefered_language"]))
	//$_SESSION["user_prefered_language"]=$user_prefered_language;
	if(isset($_SESSION["user_prefered_language"])) {
		$user_prefered_language=$_SESSION["user_prefered_language"];
	} else {
		$_SESSION["user_prefered_language"]=$user_prefered_language;
	}

	//To change language by GET link, override previous language
	if($_GET && isset($_GET["lang"])) {
		$user_prefered_language=$_GET["lang"];
		$_SESSION["user_prefered_language"]=$user_prefered_language;
	}

	//Whenever everthing fails, set en_US
	if($user_prefered_language=="")
		$user_prefered_language=="en_US";
	
	switch_to_locale($user_prefered_language);
	smartlang_define_title_apendix($user_prefered_language);
	
	return $user_prefered_language;
}

function smartlang_define_title_apendix($lang) {
	global $title_apendix;
	switch ($lang) {
		case 'notset' :
		case 'en' :
		case 'en_US' :
			$title_apendix = "USA/UK";
			break;
		case 'pt' :
		case 'pt_BR' :
			$title_apendix = "Brasil";
			break;

		case 'fr' :
		case 'fr_FR' :
			$title_apendix = "France";
			break;

		case 'es' :
		case 'es_ES' :
			$title_apendix = "Espanã/AL";
			break;

		case 'zh' :
		case 'zh_CN' :
			$title_apendix = "中国";
			break;
		default:
			$title_apendix = "Global";
			break;
	}
}

function smartlang_show_lang_options($hide_title=false, $current_location="") {
	
	global $user_prefered_language;
	
	if($current_location=="") {
		if($user_prefered_language!="")
			$current_location = $user_prefered_language;
		else
			$current_location = "notset";
	} ?>

		<?php 
		if(!$hide_title) { ?>
			<strong>Change Language:</strong>
		<?php }
		/*if($showtitle_in_h3) { ?>
			<h3 class="widget-title">Change Language</h3>
		<?php } else { ?>
			<strong>Change Language:</strong>
		<?php } */ ?>

	<?php
	#var_dump($user_prefered_language);
	#var_dump($current_location);die;
	switch ($current_location) {
		smartlang_generate_flag_links_except($current_location);
		/*case 'notset' :
		case 'en' :
		case 'en_US' :
			smartlang_generate_flag_links_except("en");
			break;
		
		case 'pt' :
		case 'pt_BR' :
			smartlang_generate_flag_links_except("pt");
			break;

		case 'fr' :
		case 'fr_FR' :
			smartlang_generate_flag_links_except("fr");
			break;

		case 'es' :
		case 'es_ES' :
			smartlang_generate_flag_links_except("es");
			break;

		case 'zh' :
		case 'zh_CN' :
			smartlang_generate_flag_links_except("zh");
			break;

		default:
			generate_flag_links_except("en");
			break;*/
	}
	/*
	if($current_location!="en" && $current_location!="en_US") { ?>
		<?php if($showtitle_in_h3) { ?>
			<h3 class="widget-title">Change Language</h3>
		<?php } else { ?>
			<strong>Change Language:</strong>
		<?php } ?>
		<a href="<?php echo get_bloginfo('url'); ?>/?lang=en_US"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/us.png" alt="Language Flag"> English</a>
	<?php } else { ?>
		<?php if($showtitle_in_h3) { ?>
			<h3 class="widget-title">Mudar Idioma</h3>
		<?php } else { ?>
			<strong>Mudar Idioma:</strong>
		<?php } ?>
		<a href="<?php echo get_bloginfo('url'); ?>/?lang=pt_BR"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/br.png" alt="Bandeira de Idioma"> Português</a>
	<?php }*/
}

function smartlang_generate_flag_links_except($except) { ?>
	<?php 
	$base_link = $_SERVER['REQUEST_URI']; 
	$base_link = preg_replace('/\?.*/', '', $base_link);
	?>
	<?php if($except!="en" && $except!="en_US") { ?>
		<a href="<?php echo $base_link; ?>?lang=en_US"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/us.png" alt="Language Flag"> English</a>
	<?php } ?>
	<?php if($except!="fr" && $except!="fr_FR") { ?>
		<a href="<?php echo $base_link; ?>?lang=fr_FR"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/fr.png" alt="Drapeau de langue"> Français</a>
	<?php } ?>
	<?php if($except!="pt" && $except!="pt_BR") { ?>
		<a href="<?php echo $base_link; ?>?lang=pt_BR"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/br.png" alt="Bandeira de Idioma"> Português</a>
	<?php } ?>
	<?php if($except!="es" && $except!="es_ES") { ?>
		<a href="<?php echo $base_link; ?>?lang=es_ES"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/es.png" alt="Bandera de Idioma"> Spañol</a>
	<?php } ?>
	<?php if($except!="zh" && $except!="zh_CN") { ?>
		<a href="<?php echo $base_link; ?>?lang=zh_CN"><img src="<?php bloginfo('stylesheet_directory'); ?>/images/flag-lang/cn.png" alt="语言标志"> 中文</a>
	<?php } ?>
<?php }

function smartlang_check_language_user_and_content($tags) {
	global $user_prefered_language;
	global $user_prefered_language_prefix;
	$user_prefered_language_prefix = substr($user_prefered_language,0,2);
	if($tags) {
		foreach ($tags as $tag) {
			# code...
			if(substr($tag->slug, 0, 5)=="lang-") {
				$content_lang = substr($tag->slug, 5, 7);
				if($user_prefered_language_prefix!=$content_lang) { ?>
					<div class="alert alert-warning">
					<strong><?php _e("Warning!", "sis-foca-js"); ?></strong> 
					<?php _e("These content is not avaiable in your language. Original content language is: ", "sis-foca-js");
					echo "<strong>".$content_lang."</strong>";
					echo "<br>";
					echo "<a href='/'>".__("Go to blog homepage", "sis-foca-js")."</a>";
					#echo "These content is not avaiable in your language";	] ?>
					</div>
				<?php }
			}
		}
	}
	#var_dump($user_prefered_language_prefix);die;
}
