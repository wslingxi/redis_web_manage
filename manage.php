<?php
include "redis.php";
$redis = RedisDb::getInstance();
$redis = $redis->redis();
$typearr = [
    1=>'string', 2=>'set', 3=>'list', 4=>'zset', 5=>'hash'
];
if ($_GET['delAll']){
    $redis->flushDB();
}
if ($_GET['delone']){
    if ($_GET['type'] == 2){
        $res = $redis->sRem($_GET['k'], $_GET['v']);
    }
    if ($_GET['type'] == 3){
        $redis->lRem($_GET['k'], $_GET['v'], 1);
    }
    if ($_GET['type'] == 4){
        $redis->zRem($_GET['k'], $_GET['v']);
    }
    if ($_GET['type'] == 5){
        $redis->hDel($_GET['k'], $_GET['key']);
    }
}
if ($_GET['del']){
    $redis->del($_GET['del']);
}
if ($_POST['submit']){
    $type = intval($_POST['type']);
    $key = $_POST['key'];
    $value = $_POST['value'];
    $expire = intval($_POST['expire']);
    if (empty($key) || empty($value)){
        $redis->set("message", "key和value都不能为空", 3);
        header("Location:manage.php");
    }
    switch ($type){
        case 1:
            if ($expire){
                $redis->set($key, $value, $expire);
            }else{
                $redis->set($key, $value);
            }
            break;
        case 2:
            foreach ($_POST['value'] as $v){
                $redis->sAdd($key, $v);
            };
        case 3:
            foreach ($_POST['value'] as $v){
                $redis->lPush($key, $v);
            };
            break;
        case 4:
            foreach ($_POST['value'] as $v){
                $redis->zAdd($key, 0, $v);
            };
            break;
        case 5:
            $hash = [];
            foreach ($_POST['key'] as $k=>$v){
                $hash[$v] = $_POST['value'][$k];
            }
            $redis->hMset($_POST['hash'], $hash);
            break;
    }
    header("Location:manage.php");
}
$list = $redis->keys("*");
$list = array_filter($list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js"></script>
    <title>Redis web管理</title>
    <style>
        body{font-size: 14px}
    </style>
</head>
<body>
<div class="container-fluid" style="width: 80%; margin-top: 80px">
    <?php if ($redis->get("message")){?>
        <div class="alert alert-danger" role="alert">
            <?php echo $redis->get("message")?>
        </div>
    <?php }?>
    <form method="post" action="manage.php">
        <div class="row">
            <div class="btn-group" role="group" aria-label="Basic example">
                <?php foreach ($typearr as $k=>$v){?>
                <button type="button" class="btn btn-primary btn-sm redis_btn" title="<?php echo in_array($v, array('list', 'zset'))?"set":$v?>" data="<?php echo $k?>" data-toggle="modal" data-target="#exampleModal">
                    添加<?php echo $v?>
                </button>
                <?php }?>
            </div>
            &nbsp;&nbsp;
            <a onclick="if(confirm('确定删除吗')){return true}else{return false}" href="manage.php?delAll=1" class="btn btn-danger btn-sm">全部删除</a>
        </div>
    </form>
    <br />
    <table class="table">
        <thead>
        <tr>
            <th width="80" scope="col">类型</th>
            <th width="100" scope="col">键</th>
            <th scope="col">值</th>
            <th width="80" scope="col">有效期</th>
            <th width="80" scope="col">操作</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($list as $key=>$value){?>
            <tr>
                <td><?php echo $typearr[$redis->type($value)]?></td>
                <td><?php echo $value?></td>
                <td>
                    <?php
                    switch ($redis->type($value)){
                        case 1:
                            echo substr($redis->get($value), 0, 100);
                            break;
                        case 2:
                            $iterator = null;
                            echo '<span style="display: none">';
                            foreach ($redis->sScan($value, $iterator, "*") as $k=>$v){
                                echo '<a href="manage.php?delone=1&type=2&k='.$value.'&v='.$v.'">删除</a> '.$k.' ) &nbsp;"'.$v.'"<br />';
                            };
                            echo '</span>';
                            echo '<a class="btn btn-outline-success btn-sm show_content" data-toggle="modal" data-target="#contentModal">查看</a>';
                            break;
                        case 3:
                            echo '<span style="display: none">';
                            foreach ($redis->lRange($value, 0, -1) as $k=>$v){
                                echo '<a href="manage.php?delone=1&type=3&k='.$value.'&v='.$v.'">删除</a> '.$k.' ) &nbsp;"'.$v.'"<br />';
                            }
                            echo '</span>';
                            echo '<a class="btn btn-outline-success btn-sm show_content" data-toggle="modal" data-target="#contentModal">查看</a>';
                            break;
                        case 4:
                            echo '<span style="display: none">';
                            foreach ($redis->zRange($value, 0, -1) as $k=>$v){
                                echo '<a href="manage.php?delone=1&type=4&k='.$value.'&v='.$v.'">删除</a> '.$k.' ) &nbsp;"'.$v.'"<br />';
                            }
                            echo '</span>';
                            echo '<a class="btn btn-outline-success btn-sm show_content" data-toggle="modal" data-target="#contentModal">查看</a>';
                            break;
                        case 5:
                            echo '<span style="display: none">';
                            foreach ($redis->hGetAll($value) as $k=>$v){
                                echo '<a href="manage.php?delone=1&type=5&k='.$value.'&key='.$k.'">删除</a> '.$k.' ) &nbsp;"'.$v.'"<br />';
                            }
                            echo '</span>';
                            echo '<a class="btn btn-outline-success btn-sm show_content" data-toggle="modal" data-target="#contentModal">查看</a>';
                            break;
                    }
                    ?>
                </td>
                <td><?php echo $redis->ttl($value)?></td>
                <td><a href="manage.php?del=<?php echo $value?>">删除</a></td>
            </tr>
        <?php }?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="manage.php">
        <div class="modal-content" id="string">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">添加</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <span>
                 <input type="hidden" id="type" name="type" value="1">
                <div class="modal-body" id="modal-body">

                </div>
            </span>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                <button type="submit" value="提交" name="submit" class="btn btn-primary">保存</button>
            </div>
        </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
            <div class="modal-content" >
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">查看</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-success" role="alert">
                        <code class="modal_content"></code>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
                </div>
            </div>
    </div>
</div>

<div id="hidden_content" style="display: none">
    <div class="modal-body string">
        <div class="row">
            <div class="col">
                <input name="key" type="text" class="form-control" placeholder="键">
            </div>
            <div class="col">
                <input name="value" type="text" class="form-control" placeholder="值">
            </div>
            <div class="col">
                <input name="expire" type="text" class="form-control" placeholder="有效期">
            </div>
        </div>
    </div>
    <div class="modal-body set">
        <div class="form-group">
            <label for="key">key</label>
            <input name="key" type="text" class="form-control" id="key" placeholder="键">
        </div>
        <div class="form-group">
            <label for="value">value</label>
            <input type="text" name="value[]" class="form-control" id="value" placeholder="值">
            <span class="set_input"></span>
            <br />
            <button type="button" class="btn btn-info plus">+</button>
        </div>
    </div>
    <div class="modal-body hash">
        <div class="form-group">
            <label for="key">key</label>
            <input name="hash" type="text" class="form-control" id="key" placeholder="键">
        </div>
        <div class="form-group">
            <label for="value">value</label>
            <div class="row">
                <div class="col">
                    <input type="text" name="key[]" class="form-control" id="value" placeholder="值">
                </div>
                <div class="col">
                    <input type="text" name="value[]" class="form-control" id="value" placeholder="值">
                </div>
            </div>
            <span class="set_input"></span>
            <br />
            <button type="button" class="btn btn-info plus">+</button>
        </div>
    </div>
</div>

<script>
    $(function () {
        setTimeout(function () {
            $(".alert-danger").hide("slow");
        }, 3000);

        $('.redis_btn').click(function () {
            var title = $(this).attr("title");
            var type = $(this).attr("data");
            $('#modal-body').html($('.' + title).html());
            $('#type').val(type);
            if (type == 5){
                $('.set_input').html('');
                $('.plus').click(function () {
                    var html = '<br /><div class="row"><div class="col"><input type="text" name="key[]" class="form-control" id="value" placeholder="值"></div><div class="col"><input type="text" name="value[]" class="form-control" id="value" placeholder="值"></div></div>';
                    $('.set_input').append(html);
                })
            }else{
                $('.set_input').html('');
                $('.plus').click(function () {
                    $('.set_input').append('<br /><input type="text" name="value[]" class="form-control" id="value" placeholder="值">');
                })
            }
        })

        $('.show_content').click(function () {
            $('.modal_content').html($(this).parent().find('span').html());
        })
    })
</script>
</body>
</html>
