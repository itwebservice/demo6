<?php
include "../../../../model/model.php";
$id_proof_url = $_POST['id_proof_url']; 
$newUrl = preg_replace('/(\/+)/','/',$id_proof_url); 
$download_url = BASE_URL.str_replace('../', '', $newUrl);?>
  <!-- Modal ID Proof -->
  <div class="modal fade" id="id_img2" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md" role="document" style="max-width: 527px;">
      <div class="modal-content">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" data-original-title="" title="">×</span></button>        
        <div class="modal-body">
            <div class="id-img">
            <?php
              if($id_proof_url == '') { ?> 
                <h3 style="color: #009898 !important; font-weight: 400 !important;">No ID Proof uploaded</h3>
                <?php 
              }
              else { ?>
                <img src="<?php echo $download_url; ?>" class="img-responsive" style="margin-top: 5px;">
                <?php
              } 
              ?>
            </div>
        </div>
      </div>
    </div>
  </div>
<script type="text/javascript">
$('#id_img2').modal('show');
</script>