<? extend('master.php') ?>
<? startblock('extra_head') ?>
<script type="text/javascript" language="javascript">
    $(document).ready(function() {
       
        build_grid("partners_grid","",<? echo $grid_fields; ?>,<? echo $grid_buttons; ?>);
        $('.checkall').click(function () {
            $('.chkRefNos').prop('checked', $(this).prop('checked'));
        });
        $("#partners_search_btn").click(function(){
            post_request_for_search("partners_grid","","partners_search");
        });        
        $("#id_reset").click(function(){
            clear_search_request("partners_grid","");
        });
        
    });
</script>
<? endblock() ?>

<? startblock('page-title') ?>
<?= $page_title ?>
<? endblock() ?>

<? startblock('content') ?>
<section class="slice color-three">
	<div class="w-section inverse p-0">
		<div class="col-12">
			<div class="portlet-content mb-4" id="search_bar"
				style="display: none">
               <?php echo $form_search; ?>
           </div>
		</div>
	</div>
</section>

<section class="slice color-three pb-4">
	<div class="w-section inverse p-0">
		<div class="card col-md-12 pb-4">
			<form method="POST" enctype="multipart/form-data" id="ListForm">
				<table id="partners_grid" align="left" style="display: none;"></table>
			</form>
		</div>
	</div>
</section>
<? endblock() ?>	
<? end_extend() ?>  
