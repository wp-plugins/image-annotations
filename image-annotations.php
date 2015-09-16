<?php
/**
 * Image Annotations
 *
 *
 * @wordpress-plugin
 * Plugin Name: Image Annotations
 * Plugin URI:  http://m03g.guriny.ru/image-annotations/
 * Description: Image Annotations plugin lets readers to leave annotations to the selected area of the image in comments. Important: for now the plugin works only with Comment Images plugin (by Tom McFarlin).
 * Version:     1.1
 * Author:      M03G
 * Author URI:  http://m03g.guriny.ru/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

add_action('wp_enqueue_scripts', 'ia_add_scripts' );
add_action('wp_enqueue_scripts', 'ia_add_style' );
add_filter('the_content', 'ia_add_form');
add_action('wp_ajax_add_annotation', 'ia_add_text');
add_action('wp_ajax_edit_annotation', 'ia_edit_text');
add_action('wp_ajax_del_annotation', 'ia_delete_text');
add_action('plugins_loaded', 'ia_init');

add_action('admin_menu', 'ia_register_menu');
add_action('admin_init', 'ia_admin_init');

add_filter('comments_array', 'ia_display_annotation');


function ia_register_menu(){
	add_menu_page( 
		'Image annotation', 'Annotations', 'manage_options', 'annotations', 'ia_admin_page', 'dashicons-testimonial', 26 
	);
}

function ia_admin_init() {
	wp_register_style('image-annotation-admin', plugins_url('/css/admin-style.css', __FILE__) );
	wp_enqueue_style('image-annotation-admin');
}

function ia_admin_page(){
	global $wpdb;
    $annotations = $wpdb->get_results("SELECT meta_id, comment_id, meta_value FROM $wpdb->commentmeta WHERE meta_key = 'annotation_to_image' ORDER BY meta_id DESC");
    echo '	<div class="wrap"><h2>Image Annotations</h2><table class="widefat fixed comments ia-admin-table">
    			<thead>
    				<tr>
    					<th class="column-response">' . __('Author', 'image-annotations') . '</th>
    					<th>' . __('Annotation', 'image-annotations') . '</th>
    					<th>' . __('In response to', 'image-annotations') . '</th>
    				</tr>
    			</thead>
    			<tfoot>
    				<tr>
    					<th class="column-response">' . __('Author', 'image-annotations') . '</th>
    					<th>' . __('Annotation', 'image-annotations') . '</th>
    					<th>' . __('In response to', 'image-annotations') . '</th>
    				</tr>
    			</tfoot>
    			<tbody>';
	foreach ($annotations as $annotation) {
		$unsercomm = unserialize(unserialize($annotation->meta_value));
		$user = get_userdata($unsercomm['user']['id']);
		$comment = get_comment($annotation->comment_id);
		$excerpt = get_comment_excerpt($annotation->comment_id);
		$permalink = get_permalink($comment->comment_post_ID);
		$post = get_post($comment->comment_post_ID);
		echo '	<tr>
					<td><a href="mailto:' . $user->user_email . '">' . $user->user_login . '</a></td>
					<td><div class="submitted-on">' . __('Submitted on', 'image-annotations') . ' <a href="' . $permalink . '#annotation-' . $annotation->meta_id . '">' . date("d.m.Y H:i", $unsercomm['annotation']['time']) . '</a></div>
					<div class="ia-admin-annotation">' . base64_decode($unsercomm['annotation']['text']) . '</div>
					<td><a href="' . $permalink . '"><strong>' . $post->post_title . '</strong></a><br><a href="' . $permalink . '#comment-' . $annotation->comment_id . '">' . $excerpt . '</a></td>
				</tr>';
		// echo $unsercomm['user']['dname'] . ' ' . date("d.m.Y H:i", $unsercomm['annotation']['time']) . ' ' . base64_decode($unsercomm['annotation']['text']) . '<br>';
	}
	echo '</tbody></table></wrap>';
}


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

function generateList($reply_key, $reply, $arrcolor) {
	global $current_user;
	$del_rep = $edit_rep = $edited_rep = $result = '';
	if (!array_key_exists($reply['user']['dname'], $arrcolor)) {
		$color_r = ia_getcolor(substr(md5($reply['user']['dname']), 0, 6));
		$arrcolor[$reply['user']['dname']] = $color_r;
	} else {
		$color_r = $arrcolor[$reply['user']['dname']];
	}
	if (array_key_exists('edit', $reply['annotation'])) {
		$edited_rep = '<span class="ia-edited" title="edited ' . date("H:i", $reply['annotation']['edit']) . '">*</span>';
	}
	if ($current_user->user_login == $reply['user']['name'] && $reply['annotation']['time'] + 900 >  time()) {
		$edit_rep = '<span class="ia-endedit" data-countdown="' . date("m/d/Y H:i:s", (strtotime(base64_decode($reply['annotation']['usertime'])) + 900)) . '" title="' . __('Time to end editing capabilities', 'image-annotations') . '"></span><div title="' . __('Edit comment (edit 15 minutes from the time of publication)', 'image-annotations') . '" class="ia-edit" nonce="' . wp_create_nonce("nonceedit") . '"></div>';
	}
	if ($current_user->user_login == $reply['user']['name'] || user_can($current_user->ID, 'administrator')) {						
		$del_rep = '<div title="' . __('Delete comment', 'image-annotations') . '" class="ia-del" nonce="' . wp_create_nonce("noncedel") . '"></div>';
	}
	$result .= '<li id="annotation-' . $reply_key . '" ia-id="' . $reply['annotation']['reply'] . '" ia-reply-to="' . $reply['annotation']['replyto'] . '" class="ia ia-annotation" style="border-left: 2px solid #' . $color_r . 
		'"><span class="ia-date" title="' . __('User time', 'image-annotations') . ': '. base64_decode($reply['annotation']['usertime']) . 
		'">'. date("d.m.Y H:i", $reply['annotation']['time']) . '</span>' . $edited_rep . '<span class="ia-author" style="color: #' . $color_r . ';">' . $reply['user']['dname'] . 
		':</span><span class="ia-text">' . base64_decode($reply['annotation']['text']) . '</span>' . $edit_rep . $del_rep . '<div class="ia-reply"></div>';
	return $result;
}

function recurMass($item, $replys, $arrcolor) {
	global $replymass;
	if (count($item) > 0) {
		$replymass .= '<ul>';
		foreach ($item as $key => $value) {
			$replymass .= generateList($key, $replys[$key], $arrcolor);
			recurMass($item[$key], $replys, $arrcolor);
			$replymass .= '</li>';
		}
		$replymass .= '</ul>';
	}
}

function ia_display_annotation($comments){
	$arrcolor = array();
	if (count($comments) > 0) {
		global $current_user, $replymass;
		foreach($comments as $comment){
			$new_ul = $list_div = '';
			if (true == get_comment_meta($comment->comment_ID, 'annotation_to_image')) {
				global $wpdb;
				$all_annotations = $wpdb->get_results("SELECT * FROM $wpdb->commentmeta WHERE comment_id = " . $comment->comment_ID . " AND meta_key = 'annotation_to_image' ORDER by meta_id");
				$annotations = array();
				$replys = array();
				$all_an = array();
				foreach ($all_annotations as $one_annot) {
					$annot_comm = unserialize(unserialize($one_annot->meta_value));
					if (array_key_exists('reply', $annot_comm['annotation'])) {
						$replys[$one_annot->meta_id] = $annot_comm;
					} else {
						$annotations[$one_annot->meta_id] = $annot_comm;
					}
					$all_an[] = array(
						'id' => $one_annot->meta_id,
						'ann' => $annot_comm,
					);
				}

				$tree = array(); 
				$sub = array( 0 => &$tree ); 

				foreach ($all_an as $item) 
				{
				    $id = $item['id'];
				    if (array_key_exists('replyto', $item['ann']['annotation'])) {
				    	$parent = $item['ann']['annotation']['replyto'];
				    } else {
				    	$parent = 0;
				    }

				    $branch = &$sub[$parent]; 
				    $branch[$id] = array(); 
				    $sub[$id] = &$branch[$id]; 
				}

				$new_ul .= '<ul class="ia-list">';
				foreach ($annotations as $key => $unsercomm) {
					$del = $edit = $edited = '';
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
					if (array_key_exists('edit', $unsercomm['annotation'])) {
						$edited = '<span class="ia-edited" title="edited ' . date("H:i", $unsercomm['annotation']['edit']) . '">*</span>';
					}
					if ($current_user->user_login == $unsercomm['user']['name'] || user_can($current_user->ID, 'administrator')) {						
						$del = '<div title="' . __('Delete comment', 'image-annotations') . '" class="ia-del" nonce="' . wp_create_nonce("noncedel") . '"></div>';
					}
					if ($current_user->user_login == $unsercomm['user']['name'] && $unsercomm['annotation']['time'] + 900 >  time()) {
						$edit = '<span class="ia-endedit" data-countdown="' . date("m/d/Y H:i:s", (strtotime(base64_decode($unsercomm['annotation']['usertime'])) + 900)) . '" title="' . __('Time to end editing capabilities', 'image-annotations') . '"></span><div title="' . __('Edit comment (edit 15 minutes from the time of publication)', 'image-annotations') . '" class="ia-edit" nonce="' . wp_create_nonce("nonceedit") . '"></div>';
					}
					$new_ul .= 	'<li id="annotation-' . $key . '" ia-id="' . $key . '" class="ia ia-annotation" style="border-left: 2px solid #' . $color . 
								'"><span class="ia-date" title="' . __('User time', 'image-annotations') . ': '. base64_decode($unsercomm['annotation']['usertime']) . 
								'">'. date("d.m.Y H:i", $unsercomm['annotation']['time']) . '</span>' . $edited . '<span class="ia-author" style="color: #' . $color . ';">' . $unsercomm['user']['dname'] . 
								':</span><span class="ia-text">' . $text_a . '</span>' . $edit . $del . '<div class="ia-reply"></div>';
					if (isset($tree[$key]) && count($tree[$key]) > 0) {
						$replymass = '';
						recurMass($tree[$key], $replys, $arrcolor);
						$new_ul .= $replymass;
					}
					$new_ul .= '</li>';
					$list_div .= '<div class="ia ia-area" ia-id="' . $key . '" style="top:' . round($unsercomm['annotation']['top'], 2) . $unit . ';left:' . round($unsercomm['annotation']['left'], 2) . $unit . ';width:' . round($unsercomm['annotation']['sidew'], 2) . $unit . ';height:' . round($unsercomm['annotation']['sideh'], 2) . $unit . ';border-color:#' . $color . '"><a href="#annotation-' . $key . '"></a></div>';
				}
				$new_ul .= '</ul>';
				$array_cont = explode('<p class="comment-image">', $comment->comment_content);
				$comment->comment_content = $array_cont[0] . '<div class="ia-main"><p class="comment-image">' . $array_cont[1] . '<div class="ia-area-vis-switch hide" vis="on" title="' . __('Show/hide selection areas', 'image-annotations') . '"></div>' . $list_div . '<div class="ia-annotations-vis-switch hide" vis="on" title="' . __('Show/hide comments', 'image-annotations') . '"></div></div>';
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
		wp_register_script('countdown', plugins_url( '/js/jquery.countdown.min.js', __FILE__ ), array('jquery','jquery-ui-core'));
		wp_enqueue_script('countdown' );
	}
}

function ia_add_style(){
	if (is_single() || is_page()) {
		wp_register_style('image-annotation', plugins_url('/css/style.css', __FILE__));
		wp_enqueue_style('image-annotation');
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

	if (isset($_POST['reply']) && !empty($_POST['reply'])) {
		$newcomm['annotation']['reply'] = (int)$_POST['reply'];
		$newcomm['annotation']['replyto'] = (int)$_POST['replyto'];
	} else {
		$newcomm['annotation']['top'] = (float)$_POST['top'];
		$newcomm['annotation']['left'] = (float)$_POST['left'];
		$newcomm['annotation']['sidew'] = (float)$_POST['sidew'];
		$newcomm['annotation']['sideh'] = (float)$_POST['sideh'];
	}
	$newcomm['annotation']['text'] = base64_encode(wp_unslash($_POST['text']));
	$newcomm['annotation']['label'] = 102; // magic numb :)
	$newcomm['annotation']['img'] = preg_replace("/[^0-9]/i","",$_POST['img']);
	$newcomm['annotation']['time'] = time();
	$newcomm['annotation']['usertime'] = base64_encode($_POST['usertime']);

	// в этом нет необходимости, ибо add_comment_meta самостоятельно серриализует массив. В следующей версии удалить.
	// Возможно, имеет смысл перейти на json_encode
	$annotation = serialize($newcomm);
	if (add_comment_meta($newcomm['annotation']['img'], 'annotation_to_image', $annotation)) {
		echo 'addok';
	}
	exit;
}

function ia_edit_text(){
	if (!wp_verify_nonce($_POST['nonce'], "nonceedit")) {
		exit(":(");
	}
	global $current_user;
	global $wpdb;
	$idannot = (int)$_POST['id'];
	$annotations = $wpdb->get_row("SELECT * FROM $wpdb->commentmeta WHERE meta_id = " . $idannot . " AND meta_key = 'annotation_to_image' LIMIT 1");
	$annotation = unserialize(unserialize($annotations->meta_value));
	if ($current_user->user_login == $annotation['user']['name'] && $annotation['annotation']['time'] + 960 >  time()) {
		$annotation['annotation']['text'] = base64_encode(wp_unslash($_POST['text']));
		$annotation['annotation']['edit'] = time();
		$newannotation = serialize(serialize($annotation));
		if($wpdb->update('wp_commentmeta', array('meta_value' => $newannotation), array('meta_id' => $idannot), array('%s'), array('%d'))) {
			echo 'editok';
		} else {
			echo 'editno';
		}
	} else {
		echo 'editno';
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
			$form.= '<p class="must-log-in">' . sprintf(__( 'You must be <a href="%s">logged in</a> to post a comment.' ), wp_login_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</p>';
		} else {
			$form.= '<textarea id="ia-textarea" placeholder="' . __('Comment', 'image-annotations') . '"></textarea><button class="ia-cancel new-annot-cancel" type="cancel">cancel</button><button nonce=' . wp_create_nonce("nonceok") . ' class="ia-ok  new-annot-ok">ok</button>';
		}
		$form.= '</div>';
		$content.= $form;
	}
	return $content;
}