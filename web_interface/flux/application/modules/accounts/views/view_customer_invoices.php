<? extend('left_panel_master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
	function invoice_delete(inv_id, accountid){
		var confirm_string = "Are you sure you want to delete this record?";
		var answer = confirm(confirm_string);
		console.log("acc_id", accountid);
		if(answer){
			$.ajax({
				type: "POST",
				url: "<?= base_url()?>/invoices/invoice_delete_statically/"+inv_id,
				data: '',
				success:function(alt) {
					window.location.href = "<?= base_url()?>/accounts/customer_invoices/" + accountid;
				},
				error: function(){
					alert("Error while deleting the invoice.");
				}
			});
		} else {
			return false;
		}		
	}
  $(document).ready(function() {
    build_grid("invoices_list","<?php echo base_url()."accounts/customer_details_json/invoices/$edit_id/"; ?>",<? echo $grid_fields; ?>,"");
    $("#left_panel_quick_search").keyup(function(){
      quick_search("accounts/customer_details_search/"+'<?php echo $accounttype?>'+"_invoices/");
    });
  });

</script>
<script type="text/javascript">
  $(document).ready(function(){
    $('.page-wrap').addClass('addon_wrap');
  });
</script>
<? endblock() ?>
<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>
<? startblock('content') ?>
<?php $permissioninfo = $this->session->userdata('permissioninfo');?>
<div id="main-wrapper">
	<div id="content" class="container-fluid">
		<div class="row">
			<div class="col-md-12 color-three border_box">
				<div class="float-left m-2 lh19">
					<nav aria-label="breadcrumb">
						<ol class="breadcrumb m-0 p-0">
							<li class="breadcrumb-item"><a
								href="<?= base_url()."accounts/".strtolower($accounttype)."_list/"; ?>"><?= gettext(ucfirst($accounttype)."s"); ?></a></li>
							<li class="breadcrumb-item"><a
								href="<?= base_url()."accounts/".strtolower($accounttype)."_edit/".$edit_id."/"; ?>"> <?php echo gettext('Profile');?> </a>
							</li>
							<li class="breadcrumb-item active" aria-current="page"><a
								href="<?= base_url()."accounts/".strtolower($accounttype)."_invoices/".$edit_id."/"; ?>">
             <?php echo gettext('Invoices');?>
           </a></li>
						</ol>
					</nav>
				</div>
				<div class="m-2 float-right">
					<a class="btn btn-light btn-hight"
						href="<?= base_url()."accounts/".strtolower($accounttype)."_edit/".$edit_id."/"; ?>">
						<i class="fa fa-fast-backward" aria-hidden="true"></i> <?php echo gettext('Back');?></a>
				</div>
			</div>
			<div class="p-4 col-md-12">
				<div class="col-md-12 p-0">
   <?php  if((isset($permissioninfo['invoices']['invoice_list']['search'])) && ($permissioninfo['invoices']['invoice_list']['search']==0)  && ($permissioninfo['login_type'] == '2' or $permissioninfo['login_type'] == '-1' or $permissioninfo['login_type'] == '0' or $permissioninfo['login_type'] == '1') or ($permissioninfo['login_type'] == '-1')){ ?>
    <div id="show_search" class="float-right col-md-4 p-0">
						<input type="text" name="left_panel_quick_search"
							id="left_panel_quick_search"
							class="form-control form-control-lg m-0"
							value="<?php echo $this->session->userdata('left_panel_search_'.$accounttype.'_invoices')?>"
							placeholder=<?php echo gettext("Search"); ?> />
					</div>
  <?php } ?>
</div>
				<div class="col-md-12 color-three slice float-left mt-4 p-0">
					<div class="card col-md-12 pb-4">
						<table id="invoices_list" align="left" style="display: none;"></table>
						<i><?php echo gettext("Note : Page will display only receipts and confirmed invoices.");?></i>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<? endblock() ?>	

<? end_extend() ?>
<script type="text/javascript">
  $(document).ready(function(){
    $(".breadcrumb li a").removeAttr("data-ripple",""); 
  });
</script>
