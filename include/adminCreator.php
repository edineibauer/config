<?php

$id = \Entity\Entity::add("login", ["nome" => "Admin", "nome_usuario" => "admin", "setor" => 1, "email" => EMAIL, "password" => ""]);

?>

<div class="row">
    <div class="container">
        <div class="card" style="background: #FFF; padding:30px; margin-top:20px; border-radius: 5px">
            <br>
            <h4>Administrador do Sistema</h4>
            <br>

            <?php
            $form = new \FormCrud\Form("login");
            $form->showForm($id, ["nome", "nome_usuario", "email", "imagem", "password"]);
            ?>

            <button onclick="loginArea()" class="waves-effect waves-light btn-large" id="btn-login" disabled="disabled"><i class="material-icons left">save</i>Login</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/config.css" />
<script src="<?=HOME?>vendor/conn/config/assets/jquery.js"></script>
<script>
    $("input[data-model='dados.password']").on("change keyup", function () {
        if($(this).val().length > 3)
            $("#btn-login").prop("disabled", false);
    });
    function loginArea() {
        post('config', 'defecon4', {}, function () {

        });
    }
</script>