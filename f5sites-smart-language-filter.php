<?php
/*
Plugin Name: F5 Sites | Smart Language Filter
Plugin URI: https://www.f5sites.com/f5sites-smart-language-filter/
Plugin Description: Use tags lang-en or lang-x to filter post content. It first try to use WooCommerce geolocalization, then basic php http geolocation.
Plugin Author: Francisco Mat
Version 1.1
Author URI: https://www.franciscomat.com/
License: GPLv3
*/
add_action('after_setup_theme', 'smartlang_set_user_language', 1, 2);
add_action('loop_start', 'smartlang_check_language_user_and_content');

add_action('pre_get_posts', 'smartlang_filter_by_tag', 1, 2);

add_filter('pre_get_document_title', 'smartlang_define_title_apendix');
#add_action('wp_enqueue_scripts', 'load_scritps_smartlang');

function load_scritps_smartlang() {
	wp_enqueue_script('bootstrap4js', plugins_url('/assets/bootstrap.min.js', __FILE__), '', time() );
	wp_enqueue_style('bootstrap4css', plugins_url('/assets/bootstrap.min.css', __FILE__), '', time() );
}

global $base_link;
$base_link = $_SERVER['SERVER_NAME']; 
$base_link = preg_replace('/\?.*/', '', $base_link);
/*
$domainfull = $_SERVER["HTTP_HOST"];
var_dump($domainfull);
$domain_exploded = explode('.', $domainfull);
var_dump($domain_exploded);
if(count($domain_exploded)>2) {
	//subdomina
	$domain_exploded = array_reverse($domain_exploded);
	var_dump($domain_exploded);
	$base_link = "www.".$domain_exploded[1].".".$domain_exploded[0];
}
die;*/
#function smartlang_filter_by_tag($post_object, $query) {
function smartlang_filter_by_tag($query) {
	if ( $query->is_home() && $query->is_main_query() ) {
		global $user_prefered_language_prefix;
		global $user_prefered_language;
		global $base_link;
		
		#var_dump($user_prefered_language_prefix);die;
		$idObj = get_category_by_slug($base_link); 
		#var_dump($idObj);die;
		
		#not configured yet (problably not f5sites shared posts)
		if(!$idObj)
			return;
		
		
		#$user_prefered_language_prefix = substr($user_prefered_language, 0,2);
		$arro = array(
			'cat' => $idObj->term_id,
			'posts_per_page' => 10,
			'tag' => "lang-".$user_prefered_language_prefix,
		);
		
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
	}
}

function smartlang_set_user_language() {
	global $user_prefered_language;
	global $user_prefered_language_prefix;
	#
	if (session_status() == PHP_SESSION_NONE)
		session_start();

	//Try WooCommerce geolocate
	if(class_exists("WC_Geolocation")) {
		$wclocation = WC_Geolocation::geolocate_ip();
		if($wclocation['country']=="")
			$user_location_georefered="BR";
		else
			$user_location_georefered = $wclocation['country'];
		#die($user_location_georefered);
	}

	//If not, then uses basic http
	if(!isset($user_location_georefered)) {
		if(function_exists("locale_accept_from_http"))
			$user_location_georefered = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		//Last case it sets en_US
		else
			$user_location_georefered = "pt_BR";
	}
	//Corrects BR to pt_BR
	if($user_location_georefered=="BR")
		$user_location_georefered="pt_BR";

	//Finally set user prefered language
	$user_prefered_language = $user_location_georefered;
	
	//Check/Save in session
	//if(!isset($_SESSION["user_prefered_language"]))
	//$_SESSION["user_prefered_language"]=$user_prefered_language;
	
	if(isset($_SESSION["user_prefered_language"]) && $_SESSION["user_prefered_language"]!="") {
		$user_prefered_language=$_SESSION["user_prefered_language"];
	} else {
		$_SESSION["user_prefered_language"]=$user_prefered_language;
	}
	
	//To change language by GET link, override previous language
	if($_GET && isset($_GET["lang"])) {
		$user_prefered_language=$_GET["lang"];
		$_SESSION["user_prefered_language"]=$user_prefered_language;
	}
	#die($_SESSION["user_prefered_language"]);

	//Whenever everthing fails, set pt_BR (was en_US)
	if($user_prefered_language=="")
		$user_prefered_language=="pt_BR";
	
	if(!is_admin())
	switch_to_locale($user_prefered_language);
	
	#die($user_prefered_language);

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
	<?php } ?>

	<?php
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
			smartlang_generate_flag_links_except("en", $show_name);
			break;
	}
}

function smartlang_generate_flag_links_current($show_name) {
	global $user_prefered_language_prefix;
	switch ($user_prefered_language_prefix) {
		//smartlang_generate_flag_links_except($current_location);
		case 'notset' :
		case 'en' :
		case 'en_US' :
			$user_prefered_language_prefix_flag="en";
			break;
		
		case 'pt' :
		case 'pt_BR' :
			$user_prefered_language_prefix_flag="pt";
			break;

		case 'fr' :
		case 'fr_FR' :
			$user_prefered_language_prefix_flag="fr";
			break;

		case 'es' :
		case 'es_ES' :
			$user_prefered_language_prefix_flag="es";
			break;

		case 'zh' :
		case 'zh_CN' :
			$user_prefered_language_prefix_flag="zh";
			break;

		default:
			$user_prefered_language_prefix_flag="en";
			break;
	}
	 ?>
		<img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/<?php echo $user_prefered_language_prefix_flag; ?>.png" alt="<?php echo $user_prefered_language_prefix; ?>" style="display: inline;"> <?php if($show_name) echo "English";?>
	<?php return;
}

function smartlang_generate_flag_links_except($except, $show_name) { 
	global $base_link;
	if(!strpos($base_link, "http"))
		$base_link_full = "https://".$base_link.strtok($_SERVER['REQUEST_URI'], "?"); ?>
	<?php if($except!="en" && $except!="en_US") { ?>
		<a href="<?php echo $base_link_full; ?>?lang=en_US"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/en.png" alt="en"> <?php if($show_name) echo "English";?></a>
	<?php } ?>
	<?php if($except!="fr" && $except!="fr_FR") { ?>
		<a href="<?php echo $base_link_full; ?>?lang=fr_FR"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/fr.png" alt="fr"> <?php if($show_name) echo "Français";?></a>
	<?php } ?>
	<?php if($except!="pt" && $except!="pt_BR") { ?>
		<a href="<?php echo $base_link_full; ?>?lang=pt_BR"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/pt.png" alt="br"> <?php if($show_name) echo "Português";?></a>
	<?php } ?>
	<?php if($except!="es" && $except!="es_ES") { ?>
		<a href="<?php echo $base_link_full; ?>?lang=es_ES"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/es.png" alt="es"> <?php if($show_name) echo "Spañol";?></a>
	<?php } ?>
	<?php if($except!="zh" && $except!="zh_CN") { ?>
		<a href="<?php echo $base_link_full; ?>?lang=zh_CN"><img src="<?php echo plugin_dir_url( __FILE__ ) ?>flags/cn.png" alt="zn"> <?php if($show_name) echo "中文";?></a>
	<?php } ?>
<?php }

function smartlang_check_language_user_and_content($tags) {
	global $user_prefered_language;
	global $user_prefered_language_prefix;
	#
	if(isset($tags)) {
		foreach ($tags as $tag) {
			if(isset($tag->slug)) {			
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
	}
}

function smartlang_recent_posts_georefer_widget($posts_per_page=10) {
	?>
	<div class="widget smartlang_recent_posts_widget">
	
	
		<?php 
		global $base_link;
		global $user_prefered_language;
		global $user_prefered_language_prefix;
		
		if(function_exists("set_shared_database_schema"))
			set_shared_database_schema();
		$idObj = get_category_by_slug($base_link); 
		#var_dump($base_link);die;
		if(!$idObj) {
			if($base_link=="www.cursowp.com.br") {
				//problably not enabled f5sites shared posts, and so no category with base_link and no tag lang-PREFIX
				$arro = array(
					'posts_per_page' => 10,
				);
			} else {
				echo "No category for posts found because F5 Sites Shared Posts not configured or enabled";
				echo "</ul></div>";
				return;
			}
		} else {
			$arro = array(
				'cat' => $idObj->term_id,
				'posts_per_page' => $posts_per_page,
				'tag' => "lang-".$user_prefered_language_prefix,
			);
		}
		
		wp_reset_query();
		$catquery = new WP_Query( $arro );
		?>
		<div class="container-fluid smartlang_recent_posts_widget_container">
			<?php
			while($catquery->have_posts()) : $catquery->the_post();
			?>
				<div class="row">
					<div class="col-md-4 contem-thumb" style="background-image: url(<?php echo get_the_post_thumbnail_url(get_the_ID(),150); ?>);">
						<?php #echo get_the_post_thumbnail_url(get_the_ID(),150); the_post_thumbnail(array(150,150)); ?>
					</div>
					<div class="col-md-8">
						<a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a>
					</div>
				</div>
			<?php endwhile;	?>
		</div>
	</div>
	<style type="text/css">
		.smartlang_recent_posts_widget_container .row {
			padding-bottom: 12px;
		}
		.smartlang_recent_posts_widget_container .contem-thumb {
			background: #EDEDED;
			min-height: 60px;
			background-position: center;
			background-size: cover;
			background-repeat: no-repeat;
		}
		.smartlang_recent_posts_widget_container div {
			line-height: 15px;
			padding-right: 5px !important;
			padding-left: 5px !important;
		}
	</style>
	<?php
}
