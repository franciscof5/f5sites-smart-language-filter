<?php
/*
Plugin Name: F5 Sites | Smart Language Filter
Plugin URI: https://www.f5sites.com/f5sites-smart-language-filter/
Plugin Description: Use tags lang-en or lang-x to filter post content. It first try to use WooCommerce geolocalization, then basic php http geolocation.
Plugin Author: Francisco Mat
Author URI: https://www.franciscomat.com/
License: GPLv3
*/
add_action('wp_loaded', 'smartlang_set_user_language');
add_action('loop_start', 'smartlang_check_language_user_and_content');

add_action('pre_get_posts', 'smartlang_filter_by_tag', 10, 2);

add_filter('pre_get_document_title', 'smartlang_define_title_apendix');

global $base_link;
$base_link = $_SERVER['SERVER_NAME']; 
$base_link = preg_replace('/\?.*/', '', $base_link);

#function smartlang_filter_by_tag($post_object, $query) {
function smartlang_filter_by_tag($query) {
	#
	#if(has_tag("lang-es", $post_object->ID))
		#echo "AAAAA $post_object->ID";
	#	return $post_object;
	#else
	#	wp_reset_postdata();

	#return NULL;
	#var_dump($query);die;
	#echo "AQUI CHAMOU";
	if ( $query->is_home() && $query->is_main_query() ) {
		#echo "AQUI FILTROU";
		#if(function_exists('set_shared_database_schema'))set_shared_database_schema();
			#wp_reset_query();
			global $base_link;
			$idObj = get_category_by_slug($base_link); 

			global $user_prefered_language;
			
			#$user_prefered_language_prefix = substr($user_prefered_language, 0,2);
			global $user_prefered_language_prefix;
			$arro = array(
				'cat' => $idObj->term_id,
				'posts_per_page' => 10,
				'tag' => "lang-".$user_prefered_language_prefix,
			);
			
			/*if($user_prefered_language=="en" || $user_prefered_language=="en_US")
				#$args[] = array("tag"=>"english");
				$arro["tag"] = "english";
			else 
				$arro["tag__not_in"] = 579;*/
			#wp_reset_query();
			#$catquery9 = new WP_Query( $arro );
			#$query = $catquery9;
		#$q = new WP_Query( 'posts_per_page=5' );
		#$query = $q;
		#$args = array(
		#	'posts_per_page' => '5',
		#);
		#$query = new WP_Query($args);
		
		$query->set('posts_per_page', -1);
		$taxquery = array(
			array(            
				'taxonomy' => 'post_tag',
				'field' => 'slug',
				#'terms' => "lang-es",
				'terms' => "lang-".$user_prefered_language_prefix,
			)
		);      
		$query->set('tax_query', $taxquery);
		/*
		$tax_query = array(
				'taxonomy' => 'post_tag', 
				'field' => 'slug', 
				'terms' => "lang-".$user_prefered_language_prefix,
            	'operator'=> 'IN' );
		$query->tax_query->queries[] = $tax_query; 
		$query->query_vars['tax_query'] = $query->tax_query->queries;
		*/
	}

	/*$tax_query = array(
				'taxonomy' => 'tag', 
				'field' => 'slug', 
				'terms' => "lang-".$user_prefered_language_prefix,
            	'operator'=> 'IN' );
	$query->tax_query->queries[] = $tax_query; 
	$query->query_vars['tax_query'] = $query->tax_query->queries;
*/
	#var_dump($user_prefered_language);die;

	#return $user_prefered_language;
}

function smartlang_set_user_language() {
	#echo "~ASDASD";die;
	global $user_prefered_language;
	global $user_prefered_language_prefix;
	#
	if (session_status() == PHP_SESSION_NONE)
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
	smartlang_define_title_apendix();
	
	$user_prefered_language_prefix = substr($user_prefered_language,0,2);
}

function smartlang_define_title_apendix() {
	#$title .=' ok';
	#return $title;
	#smartlang_set_user_language();
	global $user_prefered_language;
	#var_dump("user_prefered_language". $user_prefered_language);die;
	global $title_apendix;
	switch ($user_prefered_language) {
		case 'notset' :
		case 'en' :
		case 'en_US' :
			$title_apendix = "USA / UK";
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
			$title_apendix = "Espanã / America Latina";
			break;

		case 'zh' :
		case 'zh_CN' :
			$title_apendix = "中国";
			break;
		default:
			$title_apendix = "Global";
			break;
	}
	//echo "adsdadsas".$title;
	//die($title + $title_apendix);
	$title = get_bloginfo('title')." - ".get_bloginfo('description');
	return $title." | ".$title_apendix;
}

function smartlang_show_lang_options($hide_title=false, $show_name=false, $current_location="") {
	
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
		//smartlang_generate_flag_links_except($current_location);
		case 'notset' :
		case 'en' :
		case 'en_US' :
			smartlang_generate_flag_links_except("en", $show_name);
			break;
		
		case 'pt' :
		case 'pt_BR' :
			smartlang_generate_flag_links_except("pt", $show_name);
			break;

		case 'fr' :
		case 'fr_FR' :
			smartlang_generate_flag_links_except("fr", $show_name);
			break;

		case 'es' :
		case 'es_ES' :
			smartlang_generate_flag_links_except("es", $show_name);
			break;

		case 'zh' :
		case 'zh_CN' :
			smartlang_generate_flag_links_except("zh", $show_name);
			break;

		default:
			generate_flag_links_except("en", $show_name);
			break;
	}
	/*
	if($current_location!="en" && $current_location!="en_US") { ?>
		<?php if($showtitle_in_h3) { ?>
			<h3 class="widget-title">Change Language</h3>
		<?php } else { ?>
			<strong>Change Language:</strong>
		<?php } ?>
		<a href="<?php echo get_bloginfo('url'); ?>/?lang=en_US"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/us.png" alt="Language Flag"> English</a>
	<?php } else { ?>
		<?php if($showtitle_in_h3) { ?>
			<h3 class="widget-title">Mudar Idioma</h3>
		<?php } else { ?>
			<strong>Mudar Idioma:</strong>
		<?php } ?>
		<a href="<?php echo get_bloginfo('url'); ?>/?lang=pt_BR"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/br.png" alt="Bandeira de Idioma"> Português</a>
	<?php }*/
}

function smartlang_generate_flag_links_current($show_name) {
	global $user_prefered_language_prefix;
	 ?>
			<img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/<?php echo $user_prefered_language_prefix; ?>.png" alt="Language Flag" style="display: inline;"> <?php if($show_name) echo "English";?>
	<?php return;
}

function smartlang_generate_flag_links_except($except, $show_name) { ?>
	<?php 
		
	?>
	<?php if($except!="en" && $except!="en_US") { ?>
		<a href="<?php echo $base_link; ?>?lang=en_US"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/en.png" alt="Language Flag"> <?php if($show_name) echo "English";?></a>
	<?php } ?>
	<?php if($except!="fr" && $except!="fr_FR") { ?>
		<a href="<?php echo $base_link; ?>?lang=fr_FR"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/fr.png" alt="Drapeau de langue"> <?php if($show_name) echo "Français";?></a>
	<?php } ?>
	<?php if($except!="pt" && $except!="pt_BR") { ?>
		<a href="<?php echo $base_link; ?>?lang=pt_BR"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/pt.png" alt="Bandeira de Idioma"> <?php if($show_name) echo "Português";?></a>
	<?php } ?>
	<?php if($except!="es" && $except!="es_ES") { ?>
		<a href="<?php echo $base_link; ?>?lang=es_ES"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/es.png" alt="Bandera de Idioma"> <?php if($show_name) echo "Spañol";?></a>
	<?php } ?>
	<?php if($except!="zh" && $except!="zh_CN") { ?>
		<a href="<?php echo $base_link; ?>?lang=zh_CN"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/cn.png" alt="语言标志"> <?php if($show_name) echo "中文";?></a>
	<?php } ?>
<?php }

function smartlang_check_language_user_and_content($tags) {
	global $user_prefered_language;
	global $user_prefered_language_prefix;
	
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
					echo "<a href='/'>".__("Go to blog homepage", "sis-foca-js")." IN YOUR LANGUAGE | IN CURRENTE LANGUAGE</a>";
					#echo "These content is not avaiable in your language";	] ?>
					</div>
				<?php }
			}
		}
	}
	#var_dump($user_prefered_language_prefix);die;
}

function smartlang_recent_posts_georefer_widget() {
	#<h3><script>document.write(txt_foot_blog)</script></h3>
	?>
	<div class="widget DDDwidget_recent_entries">
		<ul style="list-style: none;">
		
			<?php 
			global $base_link;
			#if(function_exists('set_shared_database_schema'))set_shared_database_schema();
			#echo $base_link;die;
			$idObj = get_category_by_slug($base_link); 

			global $user_prefered_language;
			
			#$user_prefered_language_prefix = substr($user_prefered_language, 0,2);
			global $user_prefered_language_prefix;
			$arro = array(
				'cat' => $idObj->term_id,
				'posts_per_page' => 10,
				'tag' => "lang-".$user_prefered_language_prefix,
			);
			
			/*if($user_prefered_language=="en" || $user_prefered_language=="en_US")
				#$args[] = array("tag"=>"english");
				$arro["tag"] = "english";
			else 
				$arro["tag__not_in"] = 579;*/
			wp_reset_query();
			$catquery = new WP_Query( $arro );
			while($catquery->have_posts()) : $catquery->the_post();
			?>
			
			<li>
				<?php the_post_thumbnail(array(50,50)); ?>
				<a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a>
			</li>
			
			<?php endwhile;	?>
		</ul>
	</div>
	<?php
}