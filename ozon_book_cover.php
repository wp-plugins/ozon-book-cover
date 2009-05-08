<?php
/*
Plugin Name: Ozon Book Cover
Plugin URI: http://jishi.ru/raboty/plugin-ozon-book-cover/
Description: This plugin translates meta parameter ozon_book_id (if any) into book cover in your post. It replaced my template code.
Author: Alex Jishi Agapov <alex@jishi.ru>
Author URI: http://reading-book.ru/
Version: 0.02


	Copyright (c) 2009 Alex Jishi Agapov

	Permission is hereby granted, free of charge, to any person obtaining a
	copy of this software and associated documentation files (the "Software"),
	to deal in the Software without restriction, including without limitation
	the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
	DEALINGS IN THE SOFTWARE.

���� ������ 
*/

$ozon_upload_dir = $_SERVER[DOCUMENT_ROOT]."/wp-content/uploads";
$ozon_upload_url = "/wp-content/uploads";
$ozon_image_checked = 0;

//** INITIAL STUFFS **************************************************//

// ��������� ������� ������� � ������ (�� �����)
// �� = ������ �� ������
// ��������� ������� ����� �������
// �� = �������� ������
// ��� = �������� ����, �������� � ������

// hookit!
//add_action( 'save_post', 'check_ozon_picture', 12 );
//add_action( 'save_post', 'check_ozon_picture', 10 );
//add_action( 'publish_post', 'check_ozon_picture' );
add_action( 'edit_post', 'check_ozon_picture');


function check_ozon_picture($post_id)
{
GLOBAL $ozon_image_checked;

if ($ozon_image_checked) return 1;
$ozon_image_checked = 1;

$ozon_book = get_post_meta($post_id, "ozon_book_id", true);
list($ozon_id, $ozon_about) = explode(";",$ozon_book);
if (!$ozon_id) return 0;


$post = & get_post( $post_id );
$is_in_post_found = strpos($post->post_content, "book_".$ozon_id);

if ($is_in_post_found !=FALSE )
	{
	// ������ ����
return 1;
	}
else {
	$post_title_cleaned = htmlspecialchars($post->post_title);
	$permalink = get_permalink($post->ID);

	$ozon_img_src = get_ozon_picture($ozon_id);
if (!$ozon_img_src) return 0;

	$img_size = getimagesize("http://".$_SERVER['SERVER_NAME']."/".$ozon_img_src);

	$lil_width = round($img_size[0]/2);
	$lil_height = round($img_size[1]/2);

	$post_content = "<!-- Ozon Book Cover --><a href=\"$permalink\" rel=\"bookmark\" title=\"".sprintf(__('Permanent Link to %s', 'kubrick'), $post_title_cleaned)."\"><img class=\"alignright\" title=\"".$post_title_cleaned ."\" src=\"http://".$_SERVER['SERVER_NAME']."/".$ozon_img_src."\" alt=\"".$post_title_cleaned." \" width=\"$lil_width\" height=\"$lil_height\" style=\"border:1px solid #666; float:right;\" border:0 /></a><!-- /Ozon Book Cover -->".$post->post_content;

// Update post
  $my_post = array();
  $my_post['ID'] = $post->ID;
  $my_post['post_content'] = $post_content;

// Update the post into the database
  wp_update_post( $my_post );

	return 1;
	}
}


function get_ozon_picture($ozon_id)
{
GLOBAL $ozon_upload_dir, $ozon_upload_url;

if (!$ozon_id) return 0;

if (file_exists("$ozon_upload_dir/book_$ozon_id.gif") )
	{
	return "$ozon_upload_url/book_$ozon_id.gif";
	}
elseif (file_exists("$ozon_upload_dir/book_$ozon_id.jpg") )
	{
	return "$ozon_upload_url/book_$ozon_id.jpg";
	}

$file = join("", file("http://www.ozon.ru/webservices/OzonWebSvc.asmx/ItemDetail?ID=$ozon_id") );

// <Picture>http://www.ozon.ru/multimedia/books_covers/small/1000752630.gif</Picture>
preg_match_all("#<Picture>(.+)</Picture>#smiU", $file, $match, PREG_SET_ORDER);

$picture_url = $match[0][1];

$picture_url_noext = substr($picture_url, 0, -4);
$picture_ext = substr($picture_url, -3);
$picture_url_noext = str_replace("/small", "", $picture_url_noext);

if (!$picture_url_noext || $picture_url_noext=="") return 0;

//echo "Select $picture_url_noext.gif or $picture_url_noext.jpg? <BR><img src='$picture_url_noext.gif'> <img src='$picture_url_noext.jpg'>";


if (!copy("$picture_url_noext.jpg", "$ozon_upload_dir/book_$ozon_id.jpg") )
	{
	//echo "no such file $picture_url_noext.jpg <BR>";

	$gif_res = copy("$picture_url_noext.gif", "$ozon_upload_dir/book_$ozon_id.gif");
	if ($gif_res) 
		{
		$home_picture_url = "$ozon_upload_url/book_$ozon_id.gif";
		//echo "file $picture_url_noext.gif found <BR>";
		}
	//else echo "no such file $picture_url_noext.gif <BR>";
	}
else {
	$home_picture_url = "$ozon_upload_url/book_$ozon_id.jpg";
	//echo "file $picture_url_noext.jpg found <BR>";
	}

return $home_picture_url;
}

?>