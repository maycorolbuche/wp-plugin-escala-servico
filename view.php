<script>
	var outd_i_media;
	var outd_start_timer = false;
	var outd_interval;

	function outd_open_fullscreen() {
		var elem = document.getElementById("outd_media");
		if (elem.requestFullscreen) {
			elem.requestFullscreen();
		} else if (elem.webkitRequestFullscreen) { /* Safari */
			elem.webkitRequestFullscreen();
		} else if (elem.msRequestFullscreen) { /* IE11 */
			elem.msRequestFullscreen();
		}
	}

	function outd_start_media(){
		outd_open_fullscreen();
		if (!outd_start_timer){
			outd_start_timer = true;
			outd_interval = setInterval(outd_processa_media, 1000);
		}
	}

	function outd_end_media(){
		clearInterval(outd_interval);
		outd_start_timer = false;
	}

	function outd_processa_media(){
		if (!document.getElementById("outd_list_media").getElementsByTagName('figure')[0]){
			alert('Nenhuma mídia para exibição');
			outd_end_media();
			return false;
		}

		let indx = document.getElementById("outd_list_media").getAttribute('indx') || 0;
		if (!document.getElementById("outd_list_media").getElementsByTagName('figure')[indx]){
			indx = 0;
		}

		let item = document.getElementById("outd_list_media").getElementsByTagName('figure')[indx];

		if (item.classList.contains('start')){
			return false;
		}
		if (item.classList.contains('end')){
			document.getElementById("outd_list_media").setAttribute('indx',parseInt(indx)+1)
			item.classList.remove('active');
			item.classList.remove('start');
			item.classList.remove('end');
			return false;
		}

		item.classList.add('active');
		item.classList.add('start');

		let url = item.getAttribute("data-url");
		let duration = item.getAttribute("data-duration");
		let fit = item.getAttribute("data-fit");
		let mime = item.getAttribute("data-mime");
		let type = mime.split('/')[0];

		if (type == "image"){

			document.getElementById('outd_media').innerHTML=`
					<img src='${url}' style='object-fit: ${fit};'>`;

			setTimeout(function(){
				item.classList.add("end");
				item.classList.remove("start");
			}, duration * 1000);

		}else if ((type == "video") && (mime != "video/x-ms-wmv")){

			document.getElementById('outd_media').innerHTML=`
					<video c_ontrols autoplay muted>
					<source src='${url}' type='${mime}'>
					Navegador não suporta vídeos...`;

			document.getElementById('outd_media')
				.getElementsByTagName('video')[0]
				.onended = function(){
					item.classList.add('end');
					item.classList.remove('start');
				};

		}else{

			item.classList.add('end');
			item.classList.remove('start');

		}

	}
</script>

<style>
	#outd_media img,#outd_media video{
		width:100%;
		height:100%;
		border:0;
		margin:0;
		padding:0;
	}
	#outd_media img{
		object-fit: contain;
	}
	#outd_list_media figure.active{
		border: 2px solid #ffd100;
	}
</style>

<div class="is-content-justification-center is-layout-flex wp-container-6 wp-block-buttons">
	<div class="wp-block-button">
		<a id="outd_btn_start" class="wp-block-button__link wp-element-button" onclick='outd_start_media()'>
			Iniciar Outdoor
		</a>
	</div>
</div>

<div id="outd_media" style="margin:10px;"></div>

<?php
$outd_options = get_option('outdoor_options');
$outd_order = 'outdoor.outdoor_order';
if (isset($outd_options["random"]) && $outd_options["random"] == 1) {
    $outd_order = 'RAND()';
}

$sql = "
        SELECT
            outdoor.outdoor_id,
            posts.post_title,
            posts.post_name,
            posts.post_mime_type,
            posts.guid,
            outdoor.outdoor_status,
            outdoor.outdoor_order,
            outdoor.outdoor_start_date,
            outdoor.outdoor_end_date,
            outdoor.outdoor_object_fit,
            outdoor.outdoor_duration
        FROM " . $wpdb->prefix . "outdoor outdoor
        INNER JOIN " . $wpdb->prefix . "posts posts ON posts.id = outdoor.post_id
        WHERE 1=1
            AND outdoor.outdoor_status='active'
            AND (outdoor.outdoor_start_date IS NULL OR outdoor.outdoor_start_date = '0000-00-00' OR outdoor.outdoor_start_date <= DATE(NOW()))
            AND (outdoor.outdoor_end_date IS NULL OR outdoor.outdoor_end_date = '0000-00-00' OR outdoor.outdoor_end_date >= DATE(NOW()))
        ORDER BY $outd_order
    ";

$result = $wpdb->get_results($sql);
$i = 0;
?>

<figure id="outd_list_media" class="is-layout-flex wp-block-gallery has-nested-images columns-default is-cropped" style="align-items: center;justify-content: center;padding-top:10px;">
	<?php
foreach ($result as $linha) {
    $max_w = 100;
    $max_h = 70;
    $mime = explode('/', $linha->post_mime_type);
    $embed = '';
    $url = $linha->guid;
    $fit = $linha->outdoor_object_fit;
    switch ($mime[0]) {
        case 'image':
            $embed = "<img src='{$url}' style='width:{$max_w}px;height:{$max_h}px;object-fit: {$fit};'>";
            break;
        default:
            $embed = "<img src='" . OUTD_URL_IMG . "video.png' style='width:{$max_w}px;height:{$max_h}px;object-fit: contain;background:#F0F0F1;padding:5px;'>";
            //$embed = "
            //<video muted style='max-width:{$max_w}px;max-height:{$max_h}px;'>
            //<source src='{$url}' type='{$item['post_mime_type']}'>
            //";
            break;
    }

    echo "
				<figure
					style='width:calc({$max_w}px + 10px);padding:3px;'
					data-name='" . @$linha->post_title . "'
					data-url='" . @$linha->guid . "'
					data-duration='" . @$linha->outdoor_duration . "'
					data-mime='" . @$linha->post_mime_type . "'
					data-fit='" . @$linha->outdoor_object_fit . "'
				>
					<div>$embed</div>
					<div style='
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
						font-size:12px;
					'>$linha->post_title</div>
				</figure>
			";
    $i++;
}
?>
</figure>