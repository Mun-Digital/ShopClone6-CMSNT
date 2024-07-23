<?php
    require_once("../../config/config.php");
    require_once("../../config/function.php");
    $title = 'NẠP TIỀN ĐIỆN THOẠI | '.$CMSNT->site('tenweb');
    require_once("../../public/client/Header.php");
    require_once("../../public/client/Nav.php");
    CheckLogin();
?>

<div class="heading-page">
    <div class="container">
        <ol class="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">
            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="<?=BASE_URL('');?>"><span itemprop="name">Trang chủ</span></a>
                <span itemprop="position" content="1"></span>
            </li>
            <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                <a itemprop="item" href="<?=BASE_URL('Topup');?>"><span itemprop="name">Nạp tiền điện thoại</span></a>
                <span itemprop="position" content="3"></span>
            </li>
        </ol>
    </div>
</div>
<section class="main">
    <div class="section">
        <div class="container">
            <div class="col-sm-12">
                <div class="row mainpage-wrapper">
                    <section class="row">
                        <div class="col-md-8">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    NẠP TIỀN ĐIỆN THOẠI TỰ ĐỘNG</div>
                                <div class="panel-body">
                                    <div id="thongbao"></div>
                                    <form>
                                        <div class="form-group">
                                            <label for="amount">Dịch vụ:</label>
                                            <select id="service" onchange="totalPay()"  class="charging-amount form-control">
                                                <option value="">--Chọn dịch vụ--</option>
                                                <?php foreach($CMSNT->get_list("SELECT * FROM `services` WHERE `status` = 1 ") as $row){?>
                                                <option data-ck="<?=$row['price'];?>" value="<?=$row['id'];?>"><?=$row['name'];?></option>
                                                <?php }?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="amount">Chọn mệnh giá:</label>
                                            <select id="amount" onchange="totalPay()" class="charging-amount form-control">
                                                <option value="">--Mệnh giá--</option>
                                                <option value="10000">10,000đ</option>
                                                <option value="20000">20,000đ</option>
                                                <option value="30000">30,000đ</option>
                                                <option value="50000">50,000đ</option>
                                                <option value="100000">100,000đ</option>
                                                <option value="200000">200,000đ</option>
                                                <option value="300000">300,000đ</option>
                                                <option value="500000">500,000đ</option>
                                                <option value="1000000">1,000,000đ</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="sdt">Số điện thoại:</label>
                                            <input type="text" class="form-control" id="sdt"
                                                placeholder="Nhập số điện thoại cần nạp" value="">
                                        </div>
                                        <div id="inputMk"></div>
                                        <div class="form-group">
                                            <label for="sdt">Ghi chú:</label>
                                            <textarea class="form-control" placeholder="Nhập ghi chú đơn nạp nếu có"  id="note"></textarea>
                                        </div>
                                        <div class="form-group text-center" style="font-size:15px;">
                                            <label for="FormControlSelect">Thanh toán:</label>
                                            <strong class="text-success"><b id="ketqua">0</b> VND</strong>

                                        </div>
                                        <div class="card-footer">
                                            <button type="submit" id="Topup" class="btn btn-primary">NẠP NGAY</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                   LƯU Ý</div>
                                <div class="panel-body">
                                    <div id="inputLuuy"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    LỊCH SỬ NẠP ĐIỆN THOẠI</div>
                                <div class="panel-body">
                                    <table id="datatable" class="table table-bordered table-striped dataTable">
                                        <thead>
                                            <tr>
                                                <th>STT</th>
                                                <th>SỐ ĐIỆN THOẠI</th>
                                                <th>MỆNH GIÁ</th>
                                                <th>THANH TOÁN</th>
                                                <th>DỊCH VỤ</th>
                                                <th>GHI CHÚ</th>
                                                <th>THỜI GIAN</th>
                                                <th>TRẠNG THÁI</th>
                                                <th>LÝ DO</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                    $i = 0;
                                    foreach($CMSNT->get_list(" SELECT * FROM `service_order` WHERE `username` = '".$getUser['username']."' ORDER BY id DESC ") as $row){
                                    ?>
                                            <tr>
                                                <td><?=$i++;?></td>
                                                <td><?=$row['sdt'];?></td>
                                                <td><?=format_cash($row['amount']);?></td>
                                                <td><?=format_cash($row['pay']);?></td>
                                                <td><?=$CMSNT->get_row("SELECT * FROM `services` WHERE `id` = '".$row['service_id']."' ")['name'];?></td>
                                                <td><textarea class="form-control" readonly><?=$row['note'];?></textarea></td>
                                                <td><?=$row['createdate'];?></td>
                                                <td><?=status_service($row['status']);?></td>
                                                <td><?=$row['lydo'];?></td>
                                            </tr>
                                            <?php }?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>


                    </section>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
function showInputMk()
{
    $.ajax({
    url: "<?=BASE_URL("assets/ajaxs/showInputMk.php");?>",
    method: "GET",
    data: {
        service: $("#service").val() 
    },
    success: function(response) {
        $("#inputMk").html(response);
    }
    });

    $.ajax({
    url: "<?=BASE_URL("assets/ajaxs/showInputLuuy.php");?>",
    method: "GET",
    data: {
        service: $("#service").val() 
    },
    success: function(response) {
        $("#inputLuuy").html(response);
    }
    });
}
function totalPay()
{
    var amount = $("#amount").val();
    var price = $('#service').children("option:selected").attr('data-ck');
    var ketqua = amount - amount * price / 100;
    $('#ketqua').html(ketqua.toString().replace(/(.)(?=(\d{3})+$)/g, '$1.'));
    
    showInputMk();
}
$("#Topup").on("click", function() {

    $('#Topup').html('<i class="fa fa-spinner fa-spin"></i> Loading...').prop('disabled',
        true);
    $.ajax({
        url: "<?=BASE_URL("assets/ajaxs/OrderService.php");?>",
        method: "POST",
        data: {
            type: 'Topup',
            service: $("#service").val(),
            sdt: $("#sdt").val(),
            mk: $("#mk").val(),
            note: $("#note").val(),
            amount: $("#amount").val()
        },
        success: function(response) {
            $("#thongbao").html(response);
            $('#Topup').html(
                    'NẠP NGAY')
                .prop('disabled', false);
        }
    });
});
</script>
<script>
$(function() {
    $("#datatable").DataTable({
        "responsive": true,
        "autoWidth": false,
    });
});
</script>


<?php 
    require_once("../../public/client/Footer.php");
?>