
<div class="row">
    <div class="container">
        <form class="card" method="post" action="" enctype="multipart/form-data"
              style="background: #FFF; padding:30px; margin-top:20px; border-radius: 5px">

            <br>
            <h4>Informações do Projeto</h4>
            <div class="input-field col s12 m6">
                <input id="sitename" name="sitename" type="text" class="validate">
                <label for="sitename">Nome do Projeto</label>
            </div>
            <div class="input-field col s12 m6">
                <input id="sitedesc" name="sitedesc" type="text" class="validate">
                <label for="sitedesc">Projeto Descrição</label>
            </div>

            <div class="file-field input-field col s12 m6">
                <div class="btn">
                    <span>Logo</span>
                    <input type="file" name="logo"  ccept="image/*">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>

            <div class="file-field input-field col s12 m6">
                <div class="btn">
                    <span>Favicon</span>
                    <input type="file" name="favicon" accept="image/*">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>

            <div class="row clearfix">
                <br>
                <div class="switch col s6 m4">
                    <label>
                        HTTP
                        <input type="checkbox" name="protocol">
                        <span class="lever"></span>
                        HTTPS
                    </label>
                </div>
                <div class="switch col s6 m4">
                    <label>
                        sem WWW
                        <input type="checkbox" name="www">
                        <span class="lever"></span>
                        com WWW
                    </label>
                </div>
            </div>

            <div class="row clearfix">
                <br>
                <h4>Conexão ao Banco</h4>

                <div class="input-field col s12 m6">
                    <input id="user" name="user" type="text" class="validate" value="root">
                    <label for="user">Usuário</label>
                </div>
                <div class="input-field col s12 m6">
                    <input id="pass" name="pass" type="text" class="validate">
                    <label for="pass">Senha</label>
                </div>

                <div class="input-field col s12 m6">
                    <input id="database" name="database" type="text" class="validate" value="<?=$table?>">
                    <label for="database">Nome do Banco</label>
                </div>

                <div class="input-field col s12 m6">
                    <input id="host" name="host" value="localhost" type="text" class="validate">
                    <label for="host">Host</label>
                </div>

                <div class="input-field col s12 m6">
                    <input id="pre" name="pre" type="text" class="validate" value="<?=$pre?>">
                    <label for="pre">Prefixo das Tabelas</label>
                </div>
            </div>

            <p>
                <input type="checkbox" name="dev" id="dev" checked="checked" />
                <label for="dev">Dev</label>
            </p>

            <button type="submit" class="waves-effect waves-light btn">Criar Projeto</button>

        </form>
    </div>
</div>

<link rel="stylesheet" href="assets/config.css" />
<script src="assets/jquery.js"></script>
<script src="assets/materialize.min.js"></script>