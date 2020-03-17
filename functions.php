<?php

if(!defined('PB_DOCUMENT_PATH')){
	die( '-1' );
}

define('PB_AJAX_ER_PATH', dirname(__FILE__)."/");
define('PB_AJAX_ER_URL', PB_PLUGINS_URL . str_replace(PB_PLUGINS_PATH, "", PB_AJAX_ER_PATH));

function _pb_ajax_er_hook_register_manage_site_menu_list($results_){
	$results_['manage-ajax-er'] = array(
		'name' => 'AJAX에러보고',
		'renderer' => '_pb_ajax_er_hook_render_manage_site',
	);
	return $results_;
}
pb_hook_add_filter('pb-admin-manage-site-menu-list', "_pb_ajax_er_hook_register_manage_site_menu_list");

function _pb_ajax_er_hook_render_manage_site($menu_data_){
	$menu_list_ = pb_menu_list();
	$receiver_mail_ = pb_option_value("pb_ajax_er_receiver_mail");
	?>

	<div class="manage-site-form-panel panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">AJAX에러보고설정</h3>
		</div>
		<div class="panel-body">
			<div class="form-group">
				<label>보고메일</label>
				<input type="text" name="pb_ajax_er_receiver_mail" value="<?=$receiver_mail_?>" placeholder="개발사 메일주소 입력" class="form-control" id="pb_ajax_er_receiver_mail">
			</div>	

			<a href="javascript:_pb_ajax_er_test_report();" class="btn btn-default btn-block">테스트</a>

		</div>
	</div>
	<script type="text/javascript">
		function _pb_ajax_er_test_report(){
			PB.post('pb-ajax-er-test-report', {
				'test_mail' : $("#pb_ajax_er_receiver_mail").val(),
			}, function(result_, response_json_){
				PB.alert({
					title : "에러생성 완료",
					content : "개발사 메일에서 에러보고를 확인하세요",
				});

			}, true);
		}
	</script>

	<?php
}

function _pb_ajax_er_hook_update_site_settings($settings_data_){
	pb_option_update('pb_ajax_er_receiver_mail', $settings_data_['pb_ajax_er_receiver_mail']);
}
pb_hook_add_action('pb-admin-update-site-settings', "_pb_ajax_er_hook_update_site_settings");

pb_hook_add_action('pb_ajax_error_occurred', function($severity_, $message_, $filename_, $lineno_){
	global $_pb_ajax_er_testmode, $pbdb;

	if($_pb_ajax_er_testmode){
		$target_email_ = isset($_POST['test_mail']) ? $_POST['test_mail'] : null;
	}else{
		$target_email_ = pb_option_value("pb_ajax_er_receiver_mail");
	}

	if(!strlen($target_email_)) return;


ob_start();
?>
<table border="1" style="width: 100%; table-layout: fixed;"><tbody>
	<tr>
		<th style="background-color: #efefef; width: 100px;">파일경로</th>
		<td><?=$filename_?></td>
	</tr>
	<tr>
		<th>라인</th>
		<td><?=$lineno_?></td>
	</tr>
	<tr>
		<th>에러메시지</th>
		<td><?=$message_?></td>
	</tr>
	<tr>
		<th>마지막쿼리</th>
		<td>
			<?=$pbdb->last_query()?>
		</td>
	</tr>
</tbody></table>

<?php

$mail_body_ = ob_get_clean();
	

	$result_ = pb_mail_send($target_email_, "[고객사 AJAX 에러] ".pb_option_value("sitename"), $mail_body_);

	print_r($result_);
});

pb_add_ajax('pb-ajax-er-test-report', function(){
	global $_pb_ajax_er_testmode;
	$_pb_ajax_er_testmode = true;
	trigger_error("에러 발생 테스트", E_USER_ERROR);
	pb_end();
});

?>