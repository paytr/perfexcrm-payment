<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="panel_s">
                    <div class="panel-body">
                        <?=form_open(site_url('admin/paytr_gateway/installment_module/update'));?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                if($this->input->get('success')){
                                    echo '<div class="alert alert-success alert-dismissible" role="alert">
                                    '._l('paytr_save_success').'
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>';
                                }
                                ?>
                                <h4 class="no-margin font-semibold"><?=_l('paytr_installment_settings')?></h4>
                                <hr class="hr-panel-heading" />
                            </div>
                            <div class="col-md-12" style="margin-bottom: 10px">
                                <?=form_label(_l('paytr_installment_no_category'))?>
                                <?=form_dropdown('installment_group[0]', $installments, @$installment_item[0]['installment_count'], 'class="form-control"')?>
                            </div>
                            <?php foreach ($groups as $group): ?>
                            <div class="col-md-12" style="margin-bottom: 10px">
                                <?=form_label($group['name'], 'installment_group['.$group['id'])?>
                                <?=form_dropdown('installment_group['.$group['id'].']', $installments, @$installment_item[$group['id']]['installment_count'], 'class="form-control"')?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?=form_submit('mysubmit', 'Kaydet', 'class="btn btn-success"');?>
                        <?=form_close();?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>