<?php
if (isset($_GET['id']) && $_GET['id'] != '') {
	maxbuttons_button_restore($_GET['id']);
}
?>
<script type="text/javascript">
	window.location = "<?php admin_url() ?>admin.php?page=maxbuttons-controller&action=list&status=trash&message=1restore";
</script>
