<?php
if (isset($_GET['id']) && $_GET['id'] != '') {
	maxbuttons_button_move_to_trash($_GET['id']);
}
?>
<script type="text/javascript">
	window.location = "<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=list&message=1";
</script>
