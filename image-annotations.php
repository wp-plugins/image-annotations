<?php
/**
 * Image Annotations
 *
 *
 * @wordpress-plugin
 * Plugin Name: Image Annotations
 * Plugin URI:  http://m03g.guriny.ru/image-annotations/
 * Description: Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments. Important: for now the plugin works only with Comment Images plugin (by Tom McFarlin).
 * Version:     1.02
 * Author:      M03G
 * Author URI:  http://m03g.guriny.ru/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action('wp_enqueue_scripts', 'ia_add_scripts' );
add_action('wp_enqueue_scripts', 'ia_add_style' );
add_filter('the_content', 'ia_add_form');
add_action('wp_ajax_add_annotation', 'ia_add_text');
add_action('wp_ajax_del_annotation', 'ia_delete_text');
add_action('plugins_loaded', 'ia_init');

add_filter( 'comments_array', 'ia_display_annotation');

/**
 * Функция для изменения значения, чтобы привести его в границы определённого диапазона
 * @param int $p исходное значение
 * @param int $p нижнее значение диапазона
 * @param int $p верхнее значение диапазона
 * @return int изменённое значение
 */
function ia_changepar($p, $f, $t) {
	while ($p < $f) {
		$p+= round(($t - $f) / 3);
	}
	while ($p > $t) {
		$p-= round(($t - $f) / 3);
	}
	return $p;
}

/**
 * Функция преобразует цвет, полученный путём получения первых 6 символов от md5-хэша от ника автора комментария, в более приятный глазу, сохраняя исходный оттенок.
 * hex-код разделяется на группы по 2 символа и переводится в десятичный формат. После этого мы переводим цвет из RGB в HSV, попутно меняя насыщенность так,
 * чтобы она влезала в рамки от 40 до 80 и яркость так, чтобы она влезала в рамки от 60 до 100 (значения подобраны опытным путём). После, полученный цвет
 * переводим обратно в hex-rgb
 * @param string $tcolor hex-код цвета
 * @return string hex-код преобразованного цвета
 */
function ia_getcolor($tcolor) {
	$color = '';
	$r = hexdec(substr($tcolor,0,2));
    $g = hexdec(substr($tcolor,2,2));
    $b = hexdec(substr($tcolor,4,2));
    $maxRGB = max($r, $g, $b);
    $minRGB = min($r, $g, $b);
    $diff = $maxRGB - $minRGB;

    if ($maxRGB == $minRGB) {
    	$h = 0;
    } elseif ($maxRGB == $r) {
    	$h = 60 * ($g - $b) / ($maxRGB - $minRGB);
    	if($g < $b) {
    		$h+= 360;
    	}
    } elseif ($maxRGB == $g) {
    	$h = 60 * ($b - $r) / ($maxRGB - $minRGB) + 120;
    } elseif ($maxRGB == $b) {
    	$h = 60 * ($r - $g) / ($maxRGB - $minRGB) + 240;
    }
    $h = round($h);
    $s = 0;
    if ($maxRGB > 0) {
    	$s = ia_changepar(round(100 - $minRGB / $maxRGB * 100), 40, 80);
    }
    $v = ia_changepar(round($maxRGB / 2.55), 60, 100);
    $hi = ($h - $h % 60) / 60;
	$vmin = (100 - $s) * $v / 100;
	$a = ($v - $vmin) * ($h % 60) / 60;
	$vinc = $vmin + $a;
	$vdec = $v - $a;

	switch ($hi) {
		case 0:
			$newr = $v;
			$newg = $vinc;
			$newb = $vmin;
			break;
		case 1:
			$newr = $vdec;
			$newg = $v;
			$newb = $vmin;
			break;
		case 2:
			$newr = $vmin;
			$newg = $v;
			$newb = $vinc;
			break;
		case 3:
			$newr = $vmin;
			$newg = $vdec;
			$newb = $v;
			break;
		case 4:
			$newr = $vinc;
			$newg = $vmin;
			$newb = $v;
			break;
		case 5;
			$newr = $v;
			$newg = $vmin;
			$newb = $vdec;
			break;		
		default:
			$newr = $r;
			$newg = $g;
			$newb = $b;
			break;
	}

	$color = dechex(round($newr * 2.55)) . dechex(round($newg * 2.55)) . dechex(round($newb * 2.55));
	return $color;
}

function ia_init() {
	load_plugin_textdomain('image-annotations', false, dirname(plugin_basename(__FILE__)) . '/lang/');
} 

function ia_display_annotation($comments){
	$arrcolor = array();
	if (count($comments) > 0) {
		global $current_user;
		foreach($comments as $comment){
			$new_ul = $list_div = '';
			if (true == get_comment_meta($comment->comment_ID, 'annotation_to_image')) {
				global $wpdb;
				$annotations = $wpdb->get_results("SELECT * FROM $wpdb->commentmeta WHERE comment_id = " . $comment->comment_ID . " AND meta_key = 'annotation_to_image' ORDER by meta_id");
				$new_ul .= '<ul class="ia-list">';
				foreach ($annotations as $annot) {
					$unsercomm = unserialize(unserialize($annot->meta_value));
					$del = '';
					$text_a = base64_decode($unsercomm['annotation']['text']);
					if (!array_key_exists($unsercomm['user']['dname'], $arrcolor)) {
						$color = ia_getcolor(substr(md5($unsercomm['user']['dname']), 0, 6));
						$arrcolor[$unsercomm['user']['dname']] = $color;
					} else {
						$color = $arrcolor[$unsercomm['user']['dname']];
					}
					// проверка существует по причине использования в первой версии плагина конкретных значений размером и позиционирования выделений. Во второй версии значения относительны.
					// позже проверка будет убрана
					if (array_key_exists('label', $unsercomm['annotation']) && $unsercomm['annotation']['label'] == 102) {
						$unit = '%';
					} else {
						$unit = 'px';
					}
					if ($current_user->user_login == $unsercomm['user']['name'] || user_can($current_user->ID, 'administrator')) {
						$edit = '<div title="' . __('Edit comment', 'image-annotations') . '" class="ia-edit" nonce="' . wp_create_nonce("nonceedit") . '"></div>';
						$del = '<div title="' . __('Delete comment', 'image-annotations') . '" class="ia-del" nonce="' . wp_create_nonce("noncedel") . '"></div>';
					}
					$new_ul .= '<li ia-id="' . $annot->meta_id . '" class="ia ia-annotation" style="border-left: 2px solid #' . $color . '"><span class="ia-date" title="' . __('User time', 'image-annotations') . ': '. base64_decode($unsercomm['annotation']['usertime']) . '">'. date("d.m.Y H:i", $unsercomm['annotation']['time']) . '</span><span class="ia-author">' . $unsercomm['user']['dname'] . ':</span><span class="ia-text">' . $text_a . '</span>' . $edit . $del . '</li>';
					$list_div .= '<div class="ia ia-area" ia-id="' . $annot->meta_id . '" style="top:' . $unsercomm['annotation']['top'] . $unit . ';left:' . $unsercomm['annotation']['left'] . $unit . ';width:' . $unsercomm['annotation']['sidew'] . $unit . ';height:' . $unsercomm['annotation']['sideh'] . $unit . ';border-color:#' . $color . '"></div>';
				}
				$new_ul .= '</ul>';
				$array_cont = explode('<p class="comment-image">', $comment->comment_content);
				$comment->comment_content = $array_cont[0] . '<div class="ia-main"><p class="comment-image">' . $array_cont[1] . '<div class="ia-area-vis-switch hide" vis="on"></div>' . $list_div . '<div class="ia-annotations-vis-switch hide" vis="on" title="' . __('Show/hide comments', 'image-annotations') . '"></div></div>';
				$comment->comment_content .= '<div class="ia-annotations">';
				$comment->comment_content .= $new_ul;
				$comment->comment_content .= '</div>';
			}
		}
	}
	return $comments;
}

function ia_add_scripts() {
	if (is_single() || is_page()) {
		wp_enqueue_script('jquery-ui-core', array( 'jquery'));
		wp_enqueue_script('jquery-ui-draggable', array('jquery','jquery-ui-core'));
		wp_enqueue_script('jquery-ui-resizable', array('jquery','jquery-ui-core'));
		wp_register_script('image-annotation', plugins_url( '/js/plugin.min.js', __FILE__ ), array('jquery','jquery-ui-core'));		
		wp_enqueue_script('image-annotation' );
	}
}

function ia_add_style(){
	if (is_single() || is_page()) {
		wp_register_style('image-annotation-css', plugins_url('/css/style.css', __FILE__));
		wp_enqueue_style('image-annotation-css');
		wp_register_style('jqueryuicss', plugins_url('/css/jquery-ui.min.css', __FILE__));
		wp_enqueue_style('jqueryuicss');
	}
}

function ia_add_text(){
	if (!wp_verify_nonce($_POST['nonce'], "nonceok")) {
		exit(":(");
	} 
	global $current_user;
	global $wpdb;
	$newcomm = array();

	$newcomm['user']['id'] = $current_user->ID;
	$newcomm['user']['name'] = $current_user->user_login;
	$newcomm['user']['dname'] = $current_user->display_name;

	$newcomm['annotation']['text'] = base64_encode(wp_unslash($_POST['text']));
	$newcomm['annotation']['top'] = round((float)$_POST['top'], 2);
	$newcomm['annotation']['left'] = round((float)$_POST['left'], 2);
	$newcomm['annotation']['label'] = 102; // magic numb :)
	$newcomm['annotation']['sidew'] = round((float)$_POST['sidew'], 2);
	$newcomm['annotation']['sideh'] = round((float)$_POST['sideh'], 2);
	$newcomm['annotation']['img'] = preg_replace("/[^0-9]/i","",$_POST['img']);
	$newcomm['annotation']['time'] = time();
	$newcomm['annotation']['usertime'] = base64_encode($_POST['usertime']);

	$annotation = serialize($newcomm);
	if (add_comment_meta($newcomm['annotation']['img'], 'annotation_to_image', $annotation)) {
		echo 'addok';
	}
	exit;
}

function ia_delete_text(){
	if (!wp_verify_nonce($_POST['nonce'], "noncedel")) {
		exit(":(");
	} 
	global $current_user;
	global $wpdb;
	$idcommimg = substr($_POST['commimg'], 8) + 0;
	$idcommia = $_POST['delid'];
	$annotations = $wpdb->get_row("SELECT * FROM $wpdb->commentmeta WHERE meta_id = " . $idcommia . " AND comment_id = " . $idcommimg . " AND meta_key = 'annotation_to_image' LIMIT 1");
	$unsercomm = unserialize(unserialize($annotations->meta_value));
	if ($current_user->user_login == $unsercomm['user']['name'] || user_can($current_user->ID, 'administrator')) {
		$wpdb->delete('wp_commentmeta', array('meta_id' => $idcommia), array('%d'));
		if (!$wpdb->get_var("SELECT * FROM $wpdb->commentmeta WHERE meta_id = " . $idcommia . " AND comment_id = " . $idcommimg . " AND meta_key = 'annotation_to_image' LIMIT 1")) {
			echo 'delok';
		}
	}
	exit;
}

function ia_add_form($content){
	if (is_single() || is_page()) {
		$form = '<div class="anotText">';
		if (!is_user_logged_in()) {
			$form.= '<p class="must-log-in">' . sprintf( __( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url( apply_filters( 'the_permalink', get_permalink( $post_id ) ) ) ) . '</p>';
		} else {
			$form.= '<textarea id="ia-textarea" placeholder="' . __('Comment', 'image-annotations') . '"></textarea><button class="ia-cancel" type="cancel">cancel</button><button nonce=' . wp_create_nonce("nonceok") . ' class="ia-ok">ok</button>';
		}
		$form.= '</div>';
		$content.= $form;
	}
	return $content;
}